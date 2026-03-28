{{-- Axiomeer Architecture Diagram --}}
<style>
    .arch-wrap { font-family: 'Inter', system-ui, sans-serif; }
    .arch-grid {
        display: grid;
        grid-template-columns: repeat(8, minmax(170px, 1fr));
        gap: 10px;
        min-width: 1360px;
    }
    .arch-col { display: flex; flex-direction: column; gap: 10px; }
    .arch-head {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        padding: 6px 10px;
        border-radius: 8px;
        text-align: center;
    }
    .arch-card {
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 10px 12px;
        background: var(--bs-body-bg);
    }
    .arch-title {
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .arch-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--bs-secondary-color);
        font-size: 11px;
        line-height: 1.4;
        margin-bottom: 3px;
    }
    .arch-item:last-child { margin-bottom: 0; }
    .arch-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-secondary-color);
        font-size: 20px;
        opacity: 0.7;
    }
    .col-ui .arch-head { background: rgba(99,102,241,.14); color: #6366f1; }
    .col-ui .arch-card { border-left: 3px solid #6366f1; }
    .col-gate .arch-head { background: rgba(239,68,68,.14); color: #ef4444; }
    .col-gate .arch-card { border-left: 3px solid #ef4444; }
    .col-pipeline .arch-head { background: rgba(139,92,246,.14); color: #8b5cf6; }
    .col-pipeline .arch-card { border-left: 3px solid #8b5cf6; }
    .col-fn .arch-head { background: rgba(20,184,166,.14); color: #0f766e; }
    .col-fn .arch-card { border-left: 3px solid #0f766e; }
    .col-ai .arch-head { background: rgba(14,165,233,.14); color: #0ea5e9; }
    .col-ai .arch-card { border-left: 3px solid #0ea5e9; }
    .col-verify .arch-head { background: rgba(16,185,129,.14); color: #10b981; }
    .col-verify .arch-card { border-left: 3px solid #10b981; }
    .col-infra .arch-head { background: rgba(245,158,11,.14); color: #f59e0b; }
    .col-infra .arch-card { border-left: 3px solid #f59e0b; }
    .col-store .arch-head { background: rgba(107,114,128,.14); color: #4b5563; }
    .col-store .arch-card { border-left: 3px solid #6b7280; }
</style>

<div class="arch-wrap">
    <div class="d-flex align-items-center gap-2 mb-3">
        <iconify-icon icon="iconamoon:layers-duotone" class="fs-24 text-primary"></iconify-icon>
        <div>
            <h5 class="fw-bold mb-0">Axiomeer - System Architecture</h5>
            <span class="text-muted fs-11">End-to-end multi-agent RAG with explicit Azure Function orchestration and DAG verification</span>
        </div>
    </div>

    <div class="overflow-auto pb-2">
        <div class="arch-grid">
            <div class="arch-col col-ui">
                <div class="arch-head">1. User Experience</div>
                <div class="arch-card">
                    <div class="arch-title">UI Surfaces</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:comment-duotone"></iconify-icon>Query Chat</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>Document Upload</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:shield-yes-duotone"></iconify-icon>Safety Cockpit</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:share-2-duotone"></iconify-icon>VeriTrail DAG View</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Access and Ops</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lock-duotone"></iconify-icon>Auth and Roles</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>Domain Settings</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:chart-bar-duotone"></iconify-icon>Audit and Metrics</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-gate">
                <div class="arch-head">2. Safety Gateway</div>
                <div class="arch-card">
                    <div class="arch-title">Input Controls</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:sign-warning-duotone"></iconify-icon>Content Safety categories</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:shield-cross-duotone"></iconify-icon>Prompt Shields</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:category-duotone"></iconify-icon>Domain policy thresholding</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Output Controls</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:check-circle-1-duotone"></iconify-icon>Answer safety check</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:sign-warning-duotone"></iconify-icon>Ungrounded segment flags</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-pipeline">
                <div class="arch-head">3. Laravel Pipeline</div>
                <div class="arch-card">
                    <div class="arch-title">RAGPipelineService</div>
                    <div class="arch-item"><span class="badge bg-danger-subtle text-danger">1</span>Content Safety Agent</div>
                    <div class="arch-item"><span class="badge bg-primary-subtle text-primary">2</span>Retrieval Agent</div>
                    <div class="arch-item"><span class="badge bg-info-subtle text-info">3</span>Generation Agent</div>
                    <div class="arch-item"><span class="badge bg-success-subtle text-success">4</span>Verification Agent</div>
                    <div class="arch-item"><span class="badge bg-secondary-subtle text-secondary">5</span>Persist + Audit</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Model Router</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:route-duotone"></iconify-icon>Fast: GPT-4.1-mini</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:route-duotone"></iconify-icon>Complex: GPT-4.1</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-fn">
                <div class="arch-head">4. Azure Functions</div>
                <div class="arch-card">
                    <div class="arch-title">Python Function (SK)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lightning-2-duotone"></iconify-icon>`agents/sk_orchestrator`</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:branch-duotone"></iconify-icon>Domain plugin routing</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:database-duotone"></iconify-icon>SK ChatHistory context</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Node Function (VeriTrail)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:check-circle-1-duotone"></iconify-icon>`verifyDAG` structural checks</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:forbidden-duotone"></iconify-icon>Acyclicity validation</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lock-duotone"></iconify-icon>SHA-256 integrity hash</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-ai">
                <div class="arch-head">5. Azure AI Services</div>
                <div class="arch-card">
                    <div class="arch-title">Reasoning and Retrieval</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lightning-2-duotone"></iconify-icon>Azure OpenAI (4.1 / mini)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:search-duotone"></iconify-icon>Azure AI Search (BM25 + Vector + RRF)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:check-circle-1-duotone"></iconify-icon>AI Foundry Groundedness</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Ingestion and Accessibility</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>Document Intelligence parsing (prebuilt-layout)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:cut-duotone"></iconify-icon>Chunking (paragraphs + tables -> chunks)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:database-duotone"></iconify-icon>Embeddings (chunk vectors + query vectors)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:microphone-duotone"></iconify-icon>Speech token issuance (`/api/speech-token`)</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-verify">
                <div class="arch-head">6. Verification Layer</div>
                <div class="arch-card">
                    <div class="arch-title">Three-Ring Defense</div>
                    <div class="arch-item"><span class="badge bg-primary-subtle text-primary">50%</span>Ring 1: Groundedness</div>
                    <div class="arch-item"><span class="badge bg-success-subtle text-success">30%</span>Ring 2: LettuceDetect NLI</div>
                    <div class="arch-item"><span class="badge bg-info-subtle text-info">20%</span>Ring 3: Self-consistency</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Outputs</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:chart-bar-duotone"></iconify-icon>RAGAS metrics</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:share-2-duotone"></iconify-icon>Claim-level DAG traceability</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-infra">
                <div class="arch-head">7. Infra and Ops</div>
                <div class="arch-card">
                    <div class="arch-title">Platform Services</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lock-duotone"></iconify-icon>Azure Key Vault (provisioned; not wired in pipeline)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:comment-duotone"></iconify-icon>Service Bus queues (provisioned; not wired in pipeline)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:chart-bar-duotone"></iconify-icon>App Insights + OpenTelemetry</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Async Paths</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:send-2-duotone"></iconify-icon>Audit event publishing</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:clock-duotone"></iconify-icon>Non-blocking DAG verification</div>
                </div>
            </div>

            <div class="arch-arrow">&#x276F;</div>

            <div class="arch-col col-store">
                <div class="arch-head">8. Persistent Data</div>
                <div class="arch-card">
                    <div class="arch-title">MySQL Flexible Server</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:comment-duotone"></iconify-icon>Queries, answers, citations</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>Agent runs and traces</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:chart-bar-duotone"></iconify-icon>Evaluation metrics</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:lock-duotone"></iconify-icon>Audit logs</div>
                </div>
                <div class="arch-card">
                    <div class="arch-title">Blob Storage</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>Document storage (local disk in dev)</div>
                    <div class="arch-item"><iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>Extracted chunk artifacts (runtime)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 bg-primary-subtle mt-3">
        <div class="card-body py-2 px-3 fs-11">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <iconify-icon icon="iconamoon:route-duotone" class="text-primary fs-14"></iconify-icon>
                <strong class="text-primary">Runtime flow:</strong>
                <span>Question/upload enters Laravel</span>
                <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                <span>Safety + retrieval + generation</span>
                <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                <span>SK Azure Function orchestrates domain plugin</span>
                <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                <span>Three-ring verification + VeriTrail DAG</span>
                <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                <span>DAG verifier Azure Function signs integrity hash</span>
                <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                <span>Safety cockpit + citations + metrics persisted</span>
            </div>
        </div>
    </div>
</div>
