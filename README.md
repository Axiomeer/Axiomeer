# Axiomeer

**Multi-agent RAG platform for regulated professional domains — built on Azure AI Foundry**

Axiomeer is a grounded, auditable question-answering system for Legal, Healthcare, and Finance professionals. Every answer is verified through a three-ring hallucination defense, traced through a provenance DAG, and scored by a Safety Cockpit before reaching the user.

Built for the **Microsoft Innovate Challenge 2026**.

---

## Architecture

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           AXIOMEER SYSTEM LAYERS                             │
└──────────────────────────────────────────────────────────────────────────────┘

  [1] USER INTERFACE          [2] SAFETY GATEWAY        [3] MULTI-AGENT PIPELINE
  ──────────────────          ──────────────────        ────────────────────────
  Laravel 12 + Bootstrap 5    Azure Content Safety      Content Safety Agent
  Domain Selector             │ Hate / Violence /       │
  Query Chat UI               │ Sexual / Self-harm      Retrieval Agent
  Safety Cockpit UI           │                         │  Hybrid Search (BM25 + Vector)
  VeriTrail DAG (vis.js)      Prompt Shields            │  RRF re-ranking
  RAGAS Metrics display       │ Jailbreak detection     │  text-embedding-ada-002
  Profile & Audit Log         │ Prompt injection        │
                              │                         Generation Agent
                              Domain Policy             │  Semantic Kernel orchestrator
                              │ Per-domain guardrails   │  SK Skills: DocumentSkill /
                              │ Safety thresholds       │    ComplianceSkill / CitationSkill
                                                        │  SK Planner (domain routing)
                                                        │  SK Memory (session context)
                                                        │  Model Router:
                                                        │    simple → GPT-4.1-mini
                                                        │    complex → GPT-4.1
                                                        │
                                                        Verification Agent
                                                          Three-Ring Hallucination Defense

  [4] AZURE SERVICES          [5] VERIFICATION          [6] STORAGE
  ──────────────────          ────────────────          ───────────
  Azure OpenAI                Ring 1 (50%):             MySQL (operational)
  │ GPT-4.1-mini              │ Azure Groundedness      │ Users & auth
  │ GPT-4.1                   │ API / Foundry Agent     │ Queries & answers
  │ text-embedding-ada-002    │                         │ Metrics & citations
  │                           Ring 2 (30%):             │ Agent runs & traces
  Azure AI Search             │ LettuceDetect NLI       │ Audit log
  │ Hybrid BM25 + Vector      │ Claim classification    │
  │ HNSW (cosine, 1536-dim)   │                         Azure Blob Storage
  │ RRF fusion                Ring 3 (20%):             │ Original documents
  │ Semantic config           │ Self-consistency        │ Extracted text/pages
  │                           │ Confidence proxy        │
  Azure Content Safety        │                         Azure Key Vault
  │ Harm detection            VeriTrail DAG             │ API key storage
  │ Prompt Shields            │ Provenance nodes        │ IMDS / SAS auth
  │ Groundedness API          │ Claim-level tracing     │
  │                           │ Error localization      Azure Service Bus
  Azure AI Foundry            │                         │ audit-events queue
  │ Foundry Agent             RAGAS Metrics             │ verification-queue
  │ Groundedness check        │ Faithfulness            │ Async audit logging
  │                           │ Answer Relevancy        │
  Azure Document Intelligence │ Context Precision       Application Insights
  │ PDF/DOCX extraction       │ Context Recall          │ OpenTelemetry traces
  │ Layout analysis           │                         │ Agent run spans
  │
  Azure Speech Services
  │ Speech-to-text input
```

### Query Flow

```
User question
  → Content Safety + Prompt Shields screening
  → Hybrid search (BM25 + vector embeddings, RRF fusion)
  → Semantic Kernel routes to domain skill (DocumentSkill / ComplianceSkill)
  → GPT-4.1-mini / GPT-4.1 generates grounded answer
  → Three-ring defense scores the answer (Groundedness + NLI + Self-consistency)
  → VeriTrail DAG records full provenance
  → Safety Cockpit displayed to user (Green / Yellow / Red)
  → Audit event queued to Service Bus asynchronously
```

---

## Azure Services Used

| Service | Purpose |
|---|---|
| Azure OpenAI (GPT-4.1-mini) | Fast response generation |
| Azure OpenAI (GPT-4.1) | Complex multi-hop reasoning |
| Azure OpenAI (text-embedding-ada-002) | Vector embeddings for hybrid search |
| Azure AI Search | Hybrid BM25 + vector retrieval with HNSW |
| Azure Content Safety | Harm screening, prompt shields, groundedness |
| Azure AI Foundry | Agent-based groundedness verification |
| Azure Document Intelligence | PDF/DOCX extraction and chunking |
| Azure Speech Services | Speech-to-text query input |
| Azure Key Vault | Secure API key management |
| Azure Service Bus | Async audit event queue |
| Application Insights | OpenTelemetry tracing per agent run |

---

## Domain-Specific Safety Thresholds

| Domain | Groundedness Threshold | Approach |
|---|---|---|
| Healthcare | ≥ 90% | ComplianceSkill, strictest policy |
| Legal | ≥ 80% | ComplianceSkill, citation-required |
| Finance | ≥ 75% | DocumentSkill, balanced |

---

## Key Features

- **Hybrid Search** — BM25 keyword + 1536-dim HNSW vector retrieval fused via Reciprocal Rank Fusion
- **Semantic Kernel Orchestration** — SK Skills, Planner, and Memory in PHP; domain-aware skill routing
- **Three-Ring Hallucination Defense** — Azure Groundedness (50%) + LettuceDetect NLI (30%) + Self-consistency (20%)
- **VeriTrail DAG** — Interactive vis.js provenance graph; backward-traceable per-claim verification
- **Safety Cockpit** — Full-width Green/Yellow/Red banner with composite scores, claim-level highlights
- **Model Router** — Automatic GPT-4.1-mini → GPT-4.1 routing based on query complexity
- **RAGAS Evaluation** — Faithfulness, Answer Relevancy, Context Precision/Recall per query
- **Role-Based Access** — Admin / Analyst / Viewer with middleware enforcement
- **Responsible AI** — Per-domain safety thresholds, audit logging, OpenTelemetry tracing

---

## Stack

- **Backend**: Laravel 12, PHP 8.2
- **Frontend**: Bootstrap 5 (Reback theme), Iconify, vis.js, D3-compatible DAG
- **Database**: MySQL 8
- **Cloud**: Microsoft Azure (11 services)
- **Auth**: Laravel Breeze (session-based)

---

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Fill in Azure credentials in `.env` — see `config/azure.php` for all required keys.

To update the Azure AI Search index schema with vector support:

```bash
php scripts/update-search-index.php
```
