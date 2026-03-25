# Axiomeer — System Architecture Document
## Grounded Knowledge Assistant for Regulated Teams
### Microsoft Innovate Challenge 2026

---

## 1. System Overview

Axiomeer is a governed Retrieval-Augmented Generation (RAG) system purpose-built for compliance-critical question answering across legal, healthcare, and finance domains. It enforces factual accuracy through a **three-ring hallucination defense**, provides full **VeriTrail provenance tracing** per claim, and integrates deeply with Azure AI services for responsible AI governance.

### Architecture Diagram

```
                          +-----------------------+
                          |    Laravel Web App     |
                          |  Auth · Domain Selector|
                          |  Query UI · Provenance |
                          +-----------+-----------+
                                      |
                    +-----------------v-----------------+
                    |        Input Safety Gateway        |
                    |  Azure Content Safety (4 categories)|
                    |  Prompt Shields (jailbreak + inject) |
                    |  Domain Policy Classifier           |
                    +------------------+-----------------+
                                       |
                    +------------------v-----------------+
                    |   Multi-Agent Pipeline Orchestrator  |
                    |   (Supervisor → 4 Sequential Agents) |
                    |   OTel trace_id + span_id per agent  |
                    +--+----------+----------+----------+-+
                       |          |          |          |
              +--------v-+  +----v-----+  +-v--------+ +v-----------+
              | Content   |  | Retrieval|  |Generation| |Verification|
              | Safety    |  | Agent    |  |Agent     | |Agent       |
              | Agent     |  |          |  |          | |            |
              +-----------+  +----+-----+  +----+-----+ +-----+-----+
                                  |             |              |
                           +------v------+ +----v----+  +------v------+
                           |Azure AI     | |Azure    |  |Three-Ring   |
                           |Search       | |OpenAI   |  |Defense      |
                           |BM25 Keyword | |GPT-4.1  |  |Ring1+2+3    |
                           |Domain Filter| |mini/full|  |RAGAS Eval   |
                           +------+------+ +---------+  +------+------+
                                  |                            |
                           +------v------+              +------v------+
                           |Azure Blob   |              |VeriTrail    |
                           |Storage      |              |Provenance   |
                           |Raw Docs +   |              |DAG + Claims |
                           |Chunked Vars |              |Error Localize|
                           +-------------+              +-------------+
```

---

## 2. Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Frontend | Bootstrap 5 (Reback), Alpine.js, Iconify | Dark-mode admin dashboard, reactive UI |
| Backend | Laravel 12, PHP 8.2, XAMPP | Application framework, routing, Blade templates |
| Database | MySQL 8.0 | Queries, documents, agent runs, audit logs, RAGAS metrics |
| Auth | Laravel Breeze (Blade) | Authentication, RBAC (admin, analyst, viewer) |
| Search | Azure AI Search (Free tier) | BM25 keyword search, document indexing, domain filtering |
| LLM | Azure OpenAI (GPT-4.1-mini, GPT-4.1) | Grounded answer generation with source citation |
| Safety | Azure Content Safety | Harm screening (4 categories), Groundedness API, Prompt Shields |
| Doc Parse | Azure Document Intelligence | PDF/image/Office document parsing, table extraction |
| Speech | Azure Speech Service | Token-based STT for accessibility |
| Observability | OpenTelemetry (trace_id/span_id) | Distributed tracing across pipeline agents |
| Asset Build | Vite | CSS/JS bundling |

---

## 3. Multi-Agent Pipeline Architecture

### 3.1 Pipeline Flow

The pipeline executes **5 stages synchronously** within a single HTTP request cycle. Each stage is instrumented as an agent with its own `AgentRun` record and OpenTelemetry span.

```
Stage 1: Content Safety ─────────► Stage 2: Retrieval ─────────► Stage 3: Generation
  │ Azure Content Safety API         │ Azure AI Search              │ Azure OpenAI
  │ Prompt Shields (jailbreak)       │ BM25 keyword query           │ Model Router decision
  │ 4 harm categories (0-6)         │ Domain-scoped filtering      │ GPT-4.1-mini or GPT-4.1
  │ Blocks severity > 2             │ Top-K chunk retrieval        │ [Source N] citation format
  │                                  │                              │ Temperature 0.1
  ▼                                  ▼                              ▼
Stage 4: Verification ──────────────────────────────────────► Stage 5: Persist
  │ Ring 1: Azure Groundedness API                              │ Save answer + scores
  │ Ring 2: LettuceDetect NLI (LLM-as-judge)                  │ Persist VeriTrail DAG
  │ Ring 3: Self-Consistency Confidence                        │ Save citations with verdicts
  │ Composite: 50% G + 30% L + 20% C                          │ Compute RAGAS metrics
  │ Safety: Green ≥75% | Yellow ≥45% | Red <45%               │ Create audit log entry
```

### 3.2 Agent Types

| Agent | Role | Azure Service | Output |
|-------|------|--------------|--------|
| **Content Safety** | Screen input for harmful content + jailbreaks | Azure Content Safety + Prompt Shields | safe/blocked + categories |
| **Retrieval** | Fetch relevant document chunks | Azure AI Search (BM25) | chunks[] with scores |
| **Generation** | Produce cited, grounded answer | Azure OpenAI (model-routed) | answer text + token count |
| **Verification** | Three-ring hallucination defense + RAGAS | Azure Groundedness + LLM NLI + LLM confidence | safety scores + claim verdicts |

### 3.3 Model Router

The model router selects between GPT-4.1-mini (fast, low cost) and GPT-4.1 (complex, high accuracy) based on:

- **Question complexity**: word count, multi-hop indicators (compare, list, analyze)
- **Retrieval confidence**: few chunks or low BM25 scores suggest harder question
- **Domain**: legal and healthcare route to complex model more often
- **Scoring**: complexity score ≥ 3 triggers complex model; fallback to simple on failure

```php
// Routing decision factors (scored 0-7+)
word_count > 30           → +2
word_count > 15           → +1
multi_hop_pattern_match   → +1 each
chunks < 2                → +2
avg_search_score < 1.0    → +1
domain ∈ {legal, health}  → +1
```

---

## 4. Three-Ring Hallucination Defense

### Ring 1 — Azure Groundedness Detection API (Weight: 50%)

- **Service**: Azure Content Safety — `text:detectGroundedness`
- **Method**: Semantic comparison of answer against grounding sources
- **Output**: `ungroundedPercentage` (0-100%), `ungroundedDetails[]` with segment-level spans
- **Score**: `1.0 - (ungroundedPercentage / 100)`
- **Auto-correction**: Yellow-level answers get ungrounded segments annotated as review notices

### Ring 2 — LettuceDetect NLI Classifier (Weight: 30%)

- **Method**: LLM-as-NLI-judge — decomposes answer into individual factual claims, then classifies each as supported/unsupported by the retrieved context
- **Claim decomposition**: Extracts atomic factual statements from the answer
- **Per-claim verdict**: `supported` (with source_idx) or `unsupported`
- **Score**: `supported_claims / total_claims`
- **Output**: Claim array with verdicts, confidence scores, and source attribution
- **VeriTrail integration**: Each claim becomes a DAG node with backward edges to its source

### Ring 3 — H-Neuron Confidence Proxy (Weight: 20%)

- **Method**: Self-consistency via verbalized confidence estimation
- **Process**: LLM evaluates its own answer against sources, outputs calibrated confidence score
- **Uncertainty factors**: Identifies specific gaps in source coverage
- **Score**: 0.0-1.0 calibrated confidence
- **Fallback**: 0.5 (neutral) on API failure

### Composite Safety Score

```
composite = (groundedness × 0.50) + (lettuce × 0.30) + (confidence × 0.20)

Safety Levels:
  Green  ≥ 75%  — High confidence, fully grounded, answer delivered
  Yellow ≥ 45%  — Partial grounding, answer delivered with review notices
  Red    < 45%  — Hallucination risk too high, answer blocked
```

---

## 5. VeriTrail Provenance DAG

VeriTrail provides a directed acyclic graph (DAG) tracing every step of the pipeline, with per-claim backward tracing for full explainability.

### DAG Structure (v2.0)

```json
{
  "trace_id": "trace-...",
  "version": "2.0",
  "nodes": [
    { "id": "input", "type": "question", "label": "User Query" },
    { "id": "safety_gate", "type": "gate", "label": "Safety Gate", "passed": true },
    { "id": "retrieval", "type": "agent", "chunks_retrieved": 5 },
    { "id": "generation", "type": "agent", "tokens": 1200, "model": "gpt-4.1-mini-2" },
    { "id": "ring1", "type": "verification", "score": 0.92 },
    { "id": "ring2", "type": "verification", "score": 0.85, "claims_total": 6 },
    { "id": "ring3", "type": "verification", "score": 0.78 },
    { "id": "output", "type": "answer" },
    { "id": "claim_0", "type": "claim", "verdict": "supported", "confidence": 0.95 },
    { "id": "source_0", "type": "source", "label": "GDPR Article 6", "page": 12 }
  ],
  "edges": [
    { "from": "input", "to": "safety_gate" },
    { "from": "safety_gate", "to": "retrieval" },
    { "from": "retrieval", "to": "generation" },
    { "from": "generation", "to": "ring1" },
    { "from": "generation", "to": "ring2" },
    { "from": "generation", "to": "ring3" },
    { "from": "generation", "to": "claim_0", "label": "produces" },
    { "from": "source_0", "to": "claim_0", "label": "supports" },
    { "from": "retrieval", "to": "source_0", "label": "retrieved" }
  ],
  "metadata": {
    "total_claims": 6,
    "supported_claims": 5,
    "error_localization": [
      { "claim": "...", "localized_to": "generation", "reason": "No source support" }
    ]
  }
}
```

### Key Features

- **Backward trace per claim**: Every claim in the answer links to the source chunk that supports it
- **Error localization**: Unsupported claims are localized to the generation stage with specific reason
- **Safety gate node**: Records Content Safety + Prompt Shields results
- **Three-ring nodes**: Each ring is a separate node with its own score
- **Immutable trace**: trace_id + span_id for distributed tracing and audit compliance

---

## 6. RAGAS Evaluation Framework

Automated evaluation metrics computed for every query in the pipeline:

| Metric | Calculation | Source |
|--------|------------|--------|
| **Faithfulness** | `supported_claims / total_claims` | Ring 2 (LettuceDetect NLI) |
| **Answer Relevancy** | Azure Groundedness score | Ring 1 |
| **Context Precision** | `unique_sources_cited / total_chunks_retrieved` | Claim source attribution |
| **Context Recall** | Coverage of supported claims | Ring 2 |
| **Groundedness %** | `1.0 - ungroundedPercentage` | Azure Groundedness API |

All metrics are persisted in the `evaluation_metrics` table per query and displayed on the Evaluation dashboard with per-domain aggregation.

---

## 7. Knowledge Store

### Azure AI Search Index (`axiomeer-knowledge`)

- **Type**: BM25 keyword search (free tier)
- **Fields**: id, title, content, page_number, chunk_index, domain, document_id
- **Domain filtering**: `domain eq 'legal'` filter on search queries
- **Indexing**: Documents parsed by Document Intelligence → chunked (~500 chars) → pushed via mergeOrUpload

### Document Processing Pipeline

```
Upload → Azure Document Intelligence (prebuilt-layout)
       → Parse paragraphs + tables
       → Chunk (~500 char boundary, page-aware)
       → Azure AI Search indexing (batched, 1000/batch)
       → Store metadata (chunk_count, page_count, table_count)
```

**Supported formats**: PDF, JPEG, PNG, BMP, TIFF, HEIF, DOCX, XLSX, PPTX, HTML

---

## 8. Responsible AI Implementation

### Content Safety

- **Input screening**: 4 harm categories (Hate, Violence, Sexual, SelfHarm) with severity 0-6 scale
- **Threshold**: Severity > 2 blocks the query
- **Prompt Shields**: Jailbreak detection + indirect prompt injection detection
- **Output screening**: Answer re-checked before delivery

### Hallucination Prevention

- Three-ring defense (see Section 4)
- Red-level answers are blocked with explanation
- Yellow-level answers include review notices for ungrounded segments
- All claims are decomposed and verified individually

### Data Governance

- **RBAC**: Admin, analyst, viewer roles with middleware enforcement
- **Audit trail**: Every query logged with user, action, safety level, details
- **Domain isolation**: Queries scoped to domain via search filters
- **No training**: Azure OpenAI data not used for model training
- **Provenance**: Full VeriTrail DAG for every answer

---

## 9. Azure Services Integration Map

| Azure Service | Feature Used | RAI Role |
|--------------|-------------|----------|
| **Azure OpenAI** | GPT-4.1-mini + GPT-4.1 chat completions | Grounded generation with citations |
| **Azure AI Search** | BM25 keyword search, document indexing | Knowledge retrieval with domain scoping |
| **Azure Content Safety** | Text analysis (4 categories) | Input/output harm screening |
| **Azure Content Safety** | Groundedness Detection API | Ring 1 hallucination defense |
| **Azure Content Safety** | Prompt Shields API | Jailbreak + injection detection |
| **Azure Document Intelligence** | prebuilt-layout model | Document parsing + table extraction |
| **Azure Speech** | Token-based STT | Accessibility (voice input) |
| **Azure Blob Storage** | Document file storage | Raw document persistence |
| **Application Insights** | Connection string telemetry | Observability + monitoring |

---

## 10. Database Schema (Key Tables)

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `queries` | User questions + pipeline results | question, answer, safety_level, provenance_dag, scores |
| `documents` | Uploaded knowledge documents | title, domain_id, file_path, chunk_count, indexed_at |
| `agent_runs` | Per-agent execution records | agent_type, status, output, trace_id, span_id, latency_ms |
| `query_citations` | Source citations per answer | document_title, source_snippet, relevance_score, verdict |
| `evaluation_metrics` | RAGAS scores per query | faithfulness, answer_relevancy, context_precision, context_recall |
| `audit_logs` | Immutable audit trail | action, severity, details, user_id, query_id |
| `domains` | Domain configuration | slug, display_name, system_prompt, citation_format |

---

## 11. Deployment Architecture

### Current (Development)
- XAMPP (Apache + MySQL) on Windows
- Laravel 12 with Vite dev server
- Azure services via REST API (HTTP client)

### Target (Production)
- Azure App Service (Laravel)
- Azure MySQL Flexible Server
- Azure Blob Storage for documents
- Azure Application Insights for monitoring
- Azure Key Vault for secrets management

---

## 12. API Endpoints

### Web Routes (Auth Required)

| Route | Controller | Purpose |
|-------|-----------|---------|
| `GET /dashboard` | DashboardController | Main dashboard |
| `GET /query` | QueryController@index | Ask question page |
| `POST /query` | QueryController@store | Submit question (triggers pipeline) |
| `GET /query/{id}` | QueryController@show | View result + VeriTrail |
| `GET /documents` | DocumentController@index | Document library |
| `POST /documents` | DocumentController@store | Upload + parse + index |
| `GET /analytics` | AnalyticsController | KPIs + trends (admin/analyst) |
| `GET /audit-log` | AuditLogController | Audit trail (admin/analyst) |
| `GET /evaluation` | EvaluationController | RAGAS metrics (admin/analyst) |
| `GET /settings` | SettingsController | Service health (admin) |
| `GET /agents` | AgentPipelineController | Agent monitoring (admin) |
| `GET /responsible-ai` | (static view) | Transparency page |

### API Routes

| Route | Controller | Purpose |
|-------|-----------|---------|
| `GET /api/speech-token` | SpeechTokenController | Azure Speech token for STT |

---

## 13. Security Considerations

- CSRF protection on all forms
- Input validation on all controller store methods
- Content Safety screening before any LLM call
- Prompt Shields against jailbreak and indirect injection
- RBAC middleware on admin/analyst routes
- API keys stored in .env (not committed to git)
- Document file type validation (MIME whitelist)
- SQL injection prevention via Eloquent ORM

---

*Document version: 2.0 | Last updated: 2026-03-24*
*Axiomeer — Microsoft Innovate Challenge 2026*
