@extends('layouts.app')

@section('title', 'Responsible AI')
@section('page-title', 'Responsible AI')

@section('content')

<div class="row mb-3">
    <div class="col">
        <h4 class="fw-bold mb-0">Responsible AI Practices</h4>
        <p class="text-muted mb-0 fs-13">How Axiomeer ensures trustworthy, transparent, and accountable AI-generated answers</p>
    </div>
</div>

{{-- Transparency Card --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:eye-duotone" class="text-primary me-1"></iconify-icon>
            Transparency
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <div class="col-md-6">
                <h6 class="fw-semibold">How Answers Are Generated</h6>
                <p class="text-muted fs-13 mb-0">
                    Every answer in Axiomeer follows a governed pipeline: relevant documents are retrieved from your knowledge base using Azure AI Search,
                    then Azure OpenAI generates a response using <strong>only</strong> those retrieved sources. The system never fabricates information —
                    every claim must be traceable to an uploaded document. Answers include inline <code>[Source N]</code> citations so you can verify
                    each statement against the original material.
                </p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-semibold">VeriTrail Provenance</h6>
                <p class="text-muted fs-13 mb-0">
                    Each query produces a Provenance DAG (Directed Acyclic Graph) that traces the full path from your question through retrieval,
                    generation, and verification. This creates an auditable chain-of-custody for every answer, enabling regulatory teams to demonstrate
                    exactly how a conclusion was reached and which source documents supported it.
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Hallucination Defense --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:shield-yes-duotone" class="text-danger me-1"></iconify-icon>
            Three-Ring Hallucination Defense
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted fs-13 mb-4">
            Axiomeer employs a layered defense against AI hallucination — critical for legal, healthcare, and financial compliance where inaccurate information
            can have serious consequences. Three independent verification systems must agree before an answer is delivered.
        </p>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="avatar-sm rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center mb-3">
                        <span class="fw-bold text-primary">1</span>
                    </div>
                    <h6 class="fw-semibold">Azure Groundedness API</h6>
                    <p class="text-muted fs-13 mb-1">Weight: <strong>50%</strong></p>
                    <p class="text-muted fs-13 mb-0">
                        Microsoft's official groundedness detection API compares the generated answer against source documents at the semantic level,
                        identifying any claims not supported by the provided context.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="avatar-sm rounded-circle bg-success-subtle d-flex align-items-center justify-content-center mb-3">
                        <span class="fw-bold text-success">2</span>
                    </div>
                    <h6 class="fw-semibold">LettuceDetect</h6>
                    <p class="text-muted fs-13 mb-1">Weight: <strong>30%</strong></p>
                    <p class="text-muted fs-13 mb-0">
                        Token-level hallucination detection using the ModularRAG framework. Analyzes each token in the response to flag spans
                        that lack evidentiary support from retrieved chunks.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="avatar-sm rounded-circle bg-info-subtle d-flex align-items-center justify-content-center mb-3">
                        <span class="fw-bold text-info">3</span>
                    </div>
                    <h6 class="fw-semibold">SRLM Confidence (H-Neuron)</h6>
                    <p class="text-muted fs-13 mb-1">Weight: <strong>20%</strong></p>
                    <p class="text-muted fs-13 mb-0">
                        Uncertainty-aware reasoning that measures the model's own confidence via hidden-state analysis. Low confidence
                        triggers additional verification or answer suppression.
                    </p>
                </div>
            </div>
        </div>
        <div class="mt-4 p-3 bg-light rounded">
            <div class="d-flex flex-wrap gap-4 align-items-center">
                <span class="fw-medium">Safety Thresholds:</span>
                <span><span class="badge bg-success">Green</span> &ge; 75% — Delivered with full confidence</span>
                <span><span class="badge bg-warning">Yellow</span> 45–74% — Delivered with review warning</span>
                <span><span class="badge bg-danger">Red</span> &lt; 45% — Blocked, answer not shown to user</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Content Safety --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lock-duotone" class="text-warning me-1"></iconify-icon>
                    Content Safety
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted fs-13 mb-3">
                    Azure Content Safety screens both user inputs and AI-generated outputs across four harm categories:
                </p>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="badge bg-danger">Hate</span>
                        <span class="fs-13 text-muted">Hate speech and discriminatory language</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="badge bg-danger">Violence</span>
                        <span class="fs-13 text-muted">Violent content and threats</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="badge bg-danger">Sexual</span>
                        <span class="fs-13 text-muted">Sexually explicit material</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
                        <span class="badge bg-danger">Self-Harm</span>
                        <span class="fs-13 text-muted">Self-harm promotion or instructions</span>
                    </div>
                </div>
                <p class="text-muted fs-13 mt-3 mb-0">
                    Content exceeding severity level 2 (on a 0–6 scale) is automatically blocked. Both the question and the answer
                    are independently screened.
                </p>
            </div>
        </div>
    </div>

    {{-- Data Governance --}}
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="text-info me-1"></iconify-icon>
                    Data Governance & Privacy
                </h5>
            </div>
            <div class="card-body">
                <ul class="text-muted fs-13 mb-0">
                    <li class="mb-2"><strong>Data residency:</strong> All data stays within your Azure tenant. Documents are stored in Azure Blob Storage
                        and indexed in Azure AI Search within your selected region.</li>
                    <li class="mb-2"><strong>No training on your data:</strong> Azure OpenAI does not use your prompts or completions to train models.
                        Your compliance documents remain private.</li>
                    <li class="mb-2"><strong>Role-based access:</strong> Admin, Analyst, and Viewer roles control who can upload documents,
                        view analytics, and modify system settings.</li>
                    <li class="mb-2"><strong>Audit trail:</strong> Every query, upload, and system action is logged with timestamps, user attribution,
                        and severity levels for compliance reporting.</li>
                    <li class="mb-0"><strong>Domain isolation:</strong> Legal, Healthcare, and Finance domains maintain separate system prompts,
                        citation formats, and search indexes to prevent cross-domain data leakage.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- RAGAS Evaluation --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:certificate-badge-duotone" class="text-success me-1"></iconify-icon>
            Continuous Evaluation (RAGAS)
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted fs-13 mb-3">
            Axiomeer continuously evaluates answer quality using the RAGAS (Retrieval Augmented Generation Assessment) framework,
            measuring four dimensions of RAG system performance:
        </p>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-center p-3 border rounded">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-28 text-primary d-block mb-2"></iconify-icon>
                    <h6 class="fw-semibold mb-1">Faithfulness</h6>
                    <p class="text-muted fs-12 mb-0">Are the generated claims factually consistent with the source documents?</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 border rounded">
                    <iconify-icon icon="iconamoon:target-duotone" class="fs-28 text-info d-block mb-2"></iconify-icon>
                    <h6 class="fw-semibold mb-1">Answer Relevancy</h6>
                    <p class="text-muted fs-12 mb-0">Does the answer directly address the user's question?</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 border rounded">
                    <iconify-icon icon="iconamoon:search-duotone" class="fs-28 text-warning d-block mb-2"></iconify-icon>
                    <h6 class="fw-semibold mb-1">Context Precision</h6>
                    <p class="text-muted fs-12 mb-0">Are the retrieved chunks relevant and free of noise?</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-center p-3 border rounded">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="fs-28 text-success d-block mb-2"></iconify-icon>
                    <h6 class="fw-semibold mb-1">Context Recall</h6>
                    <p class="text-muted fs-12 mb-0">Were all relevant documents successfully retrieved?</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Azure Services Used --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <iconify-icon icon="iconamoon:cloud-duotone" class="text-primary me-1"></iconify-icon>
            Azure Services Powering Axiomeer
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Purpose</th>
                        <th>Responsible AI Role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-medium">Azure OpenAI Service</td>
                        <td>Grounded answer generation with GPT-4.1</td>
                        <td>Low-temperature factual generation, citation enforcement</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Azure AI Search</td>
                        <td>Hybrid semantic + keyword document retrieval</td>
                        <td>Ensures answers are grounded in actual uploaded documents</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Azure Content Safety</td>
                        <td>Input/output harm screening</td>
                        <td>Blocks hate, violence, sexual, self-harm content</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Groundedness Detection</td>
                        <td>Answer-to-source verification</td>
                        <td>Ring 1 of hallucination defense</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Azure Document Intelligence</td>
                        <td>Document parsing (PDF, images, Office)</td>
                        <td>Accurate extraction prevents downstream errors</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Azure Speech Service</td>
                        <td>Voice-to-text query input</td>
                        <td>Accessibility — enables hands-free interaction</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Application Insights</td>
                        <td>Telemetry and monitoring</td>
                        <td>Tracks latency, errors, and usage patterns</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">Azure AI Foundry</td>
                        <td>Agent orchestration platform</td>
                        <td>Centralized multi-agent governance</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
