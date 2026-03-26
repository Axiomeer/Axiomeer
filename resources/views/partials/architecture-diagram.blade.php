{{-- Axiomeer Architecture Diagram --}}
<style>
    .arch-diagram { font-family: 'Inter', system-ui, sans-serif; }
    .arch-layer { display: flex; flex-direction: column; gap: 8px; }
    .arch-card {
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 12px;
        border: 1px solid var(--bs-border-color);
        background: var(--bs-body-bg);
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .arch-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
    .arch-card-title { font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .arch-card-items { display: flex; flex-direction: column; gap: 3px; }
    .arch-card-item { display: flex; align-items: center; gap-5px; gap: 5px; font-size: 11px; color: var(--bs-secondary-color); }
    .arch-layer-header {
        font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
        padding: 4px 10px; border-radius: 6px; text-align: center; margin-bottom: 6px;
    }
    /* Layer color themes */
    .layer-ui .arch-card     { border-left: 3px solid #6366f1; }
    .layer-ui .arch-card-title { color: #6366f1; }
    .layer-ui .arch-layer-header { background: rgba(99,102,241,0.1); color: #6366f1; }
    .layer-gateway .arch-card { border-left: 3px solid #ef4444; }
    .layer-gateway .arch-card-title { color: #ef4444; }
    .layer-gateway .arch-layer-header { background: rgba(239,68,68,0.1); color: #ef4444; }
    .layer-agents .arch-card  { border-left: 3px solid #8b5cf6; }
    .layer-agents .arch-card-title { color: #8b5cf6; }
    .layer-agents .arch-layer-header { background: rgba(139,92,246,0.1); color: #8b5cf6; }
    .layer-azure .arch-card   { border-left: 3px solid #0ea5e9; }
    .layer-azure .arch-card-title { color: #0ea5e9; }
    .layer-azure .arch-layer-header { background: rgba(14,165,233,0.1); color: #0ea5e9; }
    .layer-verify .arch-card  { border-left: 3px solid #10b981; }
    .layer-verify .arch-card-title { color: #10b981; }
    .layer-verify .arch-layer-header { background: rgba(16,185,129,0.1); color: #10b981; }
    .layer-storage .arch-card { border-left: 3px solid #f59e0b; }
    .layer-storage .arch-card-title { color: #f59e0b; }
    .layer-storage .arch-layer-header { background: rgba(245,158,11,0.1); color: #f59e0b; }
    /* Arrow column */
    .arch-arrow-col {
        display: flex; align-items: center; justify-content: center;
        min-width: 28px; flex-shrink: 0;
    }
    .arch-arrow {
        display: flex; flex-direction: column; align-items: center; gap: 2px;
        color: var(--bs-border-color);
    }
    .arch-arrow-line { width: 2px; flex: 1; min-height: 20px; background: var(--bs-border-color); }
    .arch-arrow-head { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 7px solid var(--bs-border-color); }
    /* Agent pipeline flow */
    .agent-flow { display: flex; align-items: center; gap: 0; overflow-x: auto; }
    .agent-flow-step { text-align: center; min-width: 80px; }
    .agent-flow-step-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; font-size: 18px; }
    .agent-flow-step-label { font-size: 10px; font-weight: 600; }
    .agent-flow-arrow { width: 28px; height: 2px; background: var(--bs-border-color); flex-shrink: 0; position: relative; }
    .agent-flow-arrow::after { content: ''; position: absolute; right: -1px; top: -4px; width: 0; height: 0; border-left: 6px solid var(--bs-border-color); border-top: 5px solid transparent; border-bottom: 5px solid transparent; }
</style>

<div class="arch-diagram">
    {{-- Title row --}}
    <div class="d-flex align-items-center gap-2 mb-3">
        <iconify-icon icon="iconamoon:layers-duotone" class="fs-24 text-primary"></iconify-icon>
        <div>
            <h5 class="fw-bold mb-0">Axiomeer — System Architecture</h5>
            <span class="text-muted fs-11">Multi-agent RAG pipeline with three-ring hallucination defense on Azure</span>
        </div>
    </div>

    {{-- Main diagram: horizontal layers with arrows between --}}
    <div class="d-flex align-items-stretch gap-0 overflow-auto pb-2">

        {{-- Layer 1: User Interface --}}
        <div class="arch-layer layer-ui flex-shrink-0" style="width: 160px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                User Interface
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Laravel App</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:lock-duotone" class="fs-12"></iconify-icon>Auth &amp; Roles</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:category-duotone" class="fs-12"></iconify-icon>Domain Selector</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:comment-duotone" class="fs-12"></iconify-icon>Query Chat UI</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Management</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:profile-duotone" class="fs-12"></iconify-icon>Profile</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:settings-duotone" class="fs-12"></iconify-icon>Settings</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>Analytics &amp; Audit</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Responsible AI</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-12"></iconify-icon>Safety Cockpit</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:share-2-duotone" class="fs-12"></iconify-icon>VeriTrail DAG</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>RAGAS Metrics</div>
                </div>
            </div>
        </div>

        {{-- Arrow 1 → 2 --}}
        <div class="arch-arrow-col">
            <div style="display:flex; flex-direction:column; align-items:center; height:100%;">
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
                <div style="color: var(--bs-secondary-color); font-size:18px; line-height:1;">&#x276F;</div>
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
            </div>
        </div>

        {{-- Layer 2: Safety Gateway --}}
        <div class="arch-layer layer-gateway flex-shrink-0" style="width: 168px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>
                Safety Gateway
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Content Safety</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:sign-warning-duotone" class="fs-12 text-danger"></iconify-icon>Hate speech</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:sign-warning-duotone" class="fs-12 text-danger"></iconify-icon>Violence</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:sign-warning-duotone" class="fs-12 text-danger"></iconify-icon>Sexual content</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:sign-warning-duotone" class="fs-12 text-danger"></iconify-icon>Self-harm</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Prompt Shields</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:shield-cross-duotone" class="fs-12 text-danger"></iconify-icon>Jailbreak detection</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:shield-cross-duotone" class="fs-12 text-danger"></iconify-icon>Prompt injection</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Domain Policy</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:category-duotone" class="fs-12"></iconify-icon>Domain classifier</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-12 text-success"></iconify-icon>Guardrails check</div>
                </div>
            </div>
        </div>

        {{-- Arrow 2 → 3 --}}
        <div class="arch-arrow-col">
            <div style="display:flex; flex-direction:column; align-items:center; height:100%;">
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
                <div style="color: var(--bs-secondary-color); font-size:18px; line-height:1;">&#x276F;</div>
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
            </div>
        </div>

        {{-- Layer 3: Multi-Agent Pipeline --}}
        <div class="arch-layer layer-agents flex-shrink-0" style="width: 190px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:settings-duotone" class="me-1"></iconify-icon>
                Multi-Agent Pipeline
            </div>
            {{-- Agent flow --}}
            <div class="arch-card" style="padding: 12px;">
                <div class="arch-card-title mb-2">Agent Execution Order</div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-center" style="flex:1;">
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(239,68,68,0.12); border:2px solid #ef4444; display:flex; align-items:center; justify-content:center; margin:0 auto 3px;">
                            <iconify-icon icon="iconamoon:shield-yes-duotone" style="font-size:16px; color:#ef4444;"></iconify-icon>
                        </div>
                        <div style="font-size:9px; font-weight:600; color:#ef4444; line-height:1.2;">Content<br>Safety</div>
                    </div>
                    <div style="flex:0; width:14px; height:2px; background:var(--bs-border-color); position:relative;">
                        <div style="position:absolute; right:-3px; top:-4px; width:0; height:0; border-left:6px solid var(--bs-border-color); border-top:5px solid transparent; border-bottom:5px solid transparent;"></div>
                    </div>
                    <div class="text-center" style="flex:1;">
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(59,130,246,0.12); border:2px solid #3b82f6; display:flex; align-items:center; justify-content:center; margin:0 auto 3px;">
                            <iconify-icon icon="iconamoon:search-duotone" style="font-size:16px; color:#3b82f6;"></iconify-icon>
                        </div>
                        <div style="font-size:9px; font-weight:600; color:#3b82f6; line-height:1.2;">Retrieval</div>
                    </div>
                    <div style="flex:0; width:14px; height:2px; background:var(--bs-border-color); position:relative;">
                        <div style="position:absolute; right:-3px; top:-4px; width:0; height:0; border-left:6px solid var(--bs-border-color); border-top:5px solid transparent; border-bottom:5px solid transparent;"></div>
                    </div>
                    <div class="text-center" style="flex:1;">
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(139,92,246,0.12); border:2px solid #8b5cf6; display:flex; align-items:center; justify-content:center; margin:0 auto 3px;">
                            <iconify-icon icon="iconamoon:lightning-2-duotone" style="font-size:16px; color:#8b5cf6;"></iconify-icon>
                        </div>
                        <div style="font-size:9px; font-weight:600; color:#8b5cf6; line-height:1.2;">Generation</div>
                    </div>
                    <div style="flex:0; width:14px; height:2px; background:var(--bs-border-color); position:relative;">
                        <div style="position:absolute; right:-3px; top:-4px; width:0; height:0; border-left:6px solid var(--bs-border-color); border-top:5px solid transparent; border-bottom:5px solid transparent;"></div>
                    </div>
                    <div class="text-center" style="flex:1;">
                        <div style="width:36px; height:36px; border-radius:50%; background:rgba(16,185,129,0.12); border:2px solid #10b981; display:flex; align-items:center; justify-content:center; margin:0 auto 3px;">
                            <iconify-icon icon="iconamoon:check-circle-1-duotone" style="font-size:16px; color:#10b981;"></iconify-icon>
                        </div>
                        <div style="font-size:9px; font-weight:600; color:#10b981; line-height:1.2;">Verification</div>
                    </div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Model Router</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:route-duotone" class="fs-12"></iconify-icon>Simple → GPT-4.1-mini</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:route-duotone" class="fs-12"></iconify-icon>Complex → GPT-4.1</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Hybrid Search</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:search-duotone" class="fs-12"></iconify-icon>BM25 keyword</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:search-duotone" class="fs-12"></iconify-icon>Vector semantic</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:settings-duotone" class="fs-12"></iconify-icon>RRF re-ranking</div>
                </div>
            </div>
        </div>

        {{-- Arrow 3 ↕ 4 --}}
        <div class="arch-arrow-col">
            <div style="display:flex; flex-direction:column; align-items:center; height:100%;">
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
                <div style="color: var(--bs-secondary-color); font-size:18px; line-height:1;">&#x276F;</div>
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
            </div>
        </div>

        {{-- Layer 4: Azure Services --}}
        <div class="arch-layer layer-azure flex-shrink-0" style="width: 180px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:cloud-duotone" class="me-1"></iconify-icon>
                Azure Services
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Azure OpenAI</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-12"></iconify-icon>GPT-4.1-mini</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-12"></iconify-icon>GPT-4.1</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>text-embedding-3-large</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">AI Search &amp; Safety</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:search-duotone" class="fs-12"></iconify-icon>AI Search (Hybrid)</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-12"></iconify-icon>Content Safety</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-12"></iconify-icon>Groundedness API</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Document &amp; Speech</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:file-document-duotone" class="fs-12"></iconify-icon>Document Intelligence</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:microphone-duotone" class="fs-12"></iconify-icon>Speech-to-Text</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Infrastructure</div>
                <div class="arch-card-items">
                    <div class="arch-card-item">
                        <iconify-icon icon="iconamoon:lock-duotone" class="fs-12"></iconify-icon>
                        Key Vault
                        <span class="badge bg-success-subtle text-success fs-9 ms-1">new</span>
                    </div>
                    <div class="arch-card-item">
                        <iconify-icon icon="iconamoon:comment-duotone" class="fs-12"></iconify-icon>
                        Service Bus
                        <span class="badge bg-success-subtle text-success fs-9 ms-1">new</span>
                    </div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:cloud-duotone" class="fs-12"></iconify-icon>Blob Storage</div>
                </div>
            </div>
        </div>

        {{-- Arrow 4 → 5 --}}
        <div class="arch-arrow-col">
            <div style="display:flex; flex-direction:column; align-items:center; height:100%;">
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
                <div style="color: var(--bs-secondary-color); font-size:18px; line-height:1;">&#x276F;</div>
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
            </div>
        </div>

        {{-- Layer 5: Verification --}}
        <div class="arch-layer layer-verify flex-shrink-0" style="width: 170px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-1"></iconify-icon>
                Verification
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Three-Ring Defense</div>
                <div class="arch-card-items">
                    <div class="arch-card-item">
                        <span class="badge bg-primary-subtle text-primary fs-9" style="width:18px; text-align:center;">1</span>
                        Azure Groundedness (50%)
                    </div>
                    <div class="arch-card-item">
                        <span class="badge bg-success-subtle text-success fs-9" style="width:18px; text-align:center;">2</span>
                        LettuceDetect NLI (30%)
                    </div>
                    <div class="arch-card-item">
                        <span class="badge bg-info-subtle text-info fs-9" style="width:18px; text-align:center;">3</span>
                        SRLM Confidence (20%)
                    </div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">VeriTrail DAG</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:share-2-duotone" class="fs-12"></iconify-icon>Provenance nodes</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:route-duotone" class="fs-12"></iconify-icon>Edge tracing</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:sign-warning-duotone" class="fs-12"></iconify-icon>Error localization</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">RAGAS Metrics</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>Faithfulness</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>Answer Relevancy</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>Context Precision/Recall</div>
                </div>
            </div>
        </div>

        {{-- Arrow 5 → 6 --}}
        <div class="arch-arrow-col">
            <div style="display:flex; flex-direction:column; align-items:center; height:100%;">
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
                <div style="color: var(--bs-secondary-color); font-size:18px; line-height:1;">&#x276F;</div>
                <div style="flex:1; width:2px; background:var(--bs-border-color);"></div>
            </div>
        </div>

        {{-- Layer 6: Storage --}}
        <div class="arch-layer layer-storage flex-shrink-0" style="width: 155px;">
            <div class="arch-layer-header">
                <iconify-icon icon="iconamoon:database-duotone" class="me-1"></iconify-icon>
                Storage
            </div>
            <div class="arch-card">
                <div class="arch-card-title">MySQL (Operational)</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:profile-duotone" class="fs-12"></iconify-icon>Users &amp; auth</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:comment-duotone" class="fs-12"></iconify-icon>Queries &amp; answers</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:chart-bar-duotone" class="fs-12"></iconify-icon>Metrics &amp; citations</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:settings-duotone" class="fs-12"></iconify-icon>Agent runs &amp; traces</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:lock-duotone" class="fs-12"></iconify-icon>Audit log</div>
                </div>
            </div>
            <div class="arch-card">
                <div class="arch-card-title">Azure Blob Storage</div>
                <div class="arch-card-items">
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:file-document-duotone" class="fs-12"></iconify-icon>Original documents</div>
                    <div class="arch-card-item"><iconify-icon icon="iconamoon:file-document-duotone" class="fs-12"></iconify-icon>Extracted text/pages</div>
                </div>
            </div>
        </div>

    </div>

    {{-- Legend --}}
    <div class="row g-2 mt-3">
        <div class="col-12">
            <div class="card border-0 bg-light-subtle">
                <div class="card-body py-2 px-3">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <span class="fw-semibold fs-11 text-muted">LEGEND:</span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#6366f1; border-radius:2px;"></span>
                            UI Layer
                        </span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#ef4444; border-radius:2px;"></span>
                            Safety Gateway
                        </span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#8b5cf6; border-radius:2px;"></span>
                            Agent Pipeline
                        </span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#0ea5e9; border-radius:2px;"></span>
                            Azure Services
                        </span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#10b981; border-radius:2px;"></span>
                            Verification
                        </span>
                        <span class="fs-11 d-flex align-items-center gap-1">
                            <span style="display:inline-block; width:12px; height:12px; background:#f59e0b; border-radius:2px;"></span>
                            Storage
                        </span>
                        <span class="badge bg-success-subtle text-success fs-9 ms-auto">new — new in this sprint</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data flow summary --}}
    <div class="row g-2 mt-1">
        <div class="col-12">
            <div class="card border-0 bg-primary-subtle">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap fs-11">
                        <iconify-icon icon="iconamoon:route-duotone" class="text-primary fs-14"></iconify-icon>
                        <strong class="text-primary">Query Flow:</strong>
                        <span>User submits question</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>Content Safety + Prompt Shields screening</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>Hybrid search retrieves relevant chunks</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>GPT generates grounded answer</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>Three-ring defense scores the answer</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>VeriTrail DAG records provenance</span>
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-muted"></iconify-icon>
                        <span>Safety Cockpit shown to user</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
