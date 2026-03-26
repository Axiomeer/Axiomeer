{{-- Helper Chatbot Widget with Agent Navigation & Voice --}}
<style>
    .chatbot-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1050;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--bs-primary);
        color: white;
        border: none;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    .chatbot-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 24px rgba(0,0,0,0.3);
    }
    .chatbot-fab .badge-dot {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background: var(--bs-success);
        border-radius: 50%;
        border: 2px solid white;
    }
    .chatbot-panel {
        position: fixed;
        bottom: 90px;
        right: 24px;
        z-index: 1050;
        width: 380px;
        max-height: 520px;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
    }
    .chatbot-panel.open { display: flex; animation: slideUp 0.3s ease; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .chatbot-messages { flex: 1; overflow-y: auto; padding: 16px; max-height: 360px; }
    .chatbot-msg { margin-bottom: 12px; animation: fadeIn 0.2s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .chatbot-msg-bot .chatbot-bubble { background: var(--bs-light); border-radius: 12px 12px 12px 4px; padding: 10px 14px; font-size: 13px; }
    .chatbot-msg-user .chatbot-bubble { background: var(--bs-primary); color: white; border-radius: 12px 12px 4px 12px; padding: 10px 14px; font-size: 13px; margin-left: auto; max-width: 80%; }
    .chatbot-msg-user { text-align: right; }
    .chatbot-quick-actions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
    .chatbot-quick-btn { font-size: 11px; padding: 4px 10px; border-radius: 20px; border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); cursor: pointer; transition: all 0.2s; }
    .chatbot-quick-btn:hover { border-color: var(--bs-primary); color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.05); }
    .chatbot-nav-action { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; background: rgba(var(--bs-primary-rgb), 0.1); border: 1px solid rgba(var(--bs-primary-rgb), 0.2); color: var(--bs-primary); cursor: pointer; font-size: 12px; text-decoration: none; margin-top: 6px; transition: all 0.2s; }
    .chatbot-nav-action:hover { background: rgba(var(--bs-primary-rgb), 0.2); color: var(--bs-primary); }
    .chatbot-mic-btn { background: none; border: none; color: var(--bs-secondary); cursor: pointer; padding: 4px; transition: color 0.2s; }
    .chatbot-mic-btn:hover { color: var(--bs-primary); }
    .chatbot-mic-btn.recording { color: var(--bs-danger); animation: pulse 1s infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }
    .chatbot-speaking { position: relative; }
    .chatbot-speaking::after { content: ''; position: absolute; top: 4px; right: -16px; width: 8px; height: 8px; border-radius: 50%; background: var(--bs-info); animation: pulse 0.8s infinite; }
    .chatbot-mode-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; background: rgba(255,255,255,0.2); }
</style>

{{-- Floating Button --}}
<button class="chatbot-fab" id="chatbotFab" title="Need help?">
    <iconify-icon icon="iconamoon:comment-dots-duotone" class="fs-24" id="chatbotFabIcon"></iconify-icon>
    <span class="badge-dot"></span>
</button>

{{-- Chat Panel --}}
<div class="chatbot-panel" id="chatbotPanel">
    {{-- Header --}}
    <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between" style="background: var(--bs-primary); color: white; border-radius: 16px 16px 0 0;">
        <div class="d-flex align-items-center gap-2">
            <iconify-icon icon="iconamoon:lightning-2-duotone" class="fs-20"></iconify-icon>
            <div>
                <span class="fw-semibold fs-14">Axiomeer Guide</span>
                <span class="chatbot-mode-badge ms-1" id="chatbotModeBadge">Agent</span>
                <span class="d-block fs-10 opacity-75">Ask questions or say "go to..."</span>
            </div>
        </div>
        <div class="d-flex align-items-center gap-1">
            <button class="btn btn-sm text-white p-0" id="chatbotSpeakerToggle" title="Toggle voice responses">
                <iconify-icon icon="iconamoon:volume-up-duotone" class="fs-18" id="chatbotSpeakerIcon"></iconify-icon>
            </button>
            <button class="btn btn-sm text-white p-0" id="chatbotClose">
                <iconify-icon icon="iconamoon:sign-times-duotone" class="fs-20"></iconify-icon>
            </button>
        </div>
    </div>

    {{-- Messages --}}
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chatbot-msg chatbot-msg-bot">
            <div class="chatbot-bubble">
                Hi! I'm your Axiomeer guide. I can help you <strong>navigate pages</strong>, explain features, or answer questions.
                <br><br>Try saying <em>"go to documents"</em> or <em>"upload a file"</em> and I'll take you there!
            </div>
            <div class="chatbot-quick-actions">
                <button class="chatbot-quick-btn" data-question="Go to ask question">Ask a question</button>
                <button class="chatbot-quick-btn" data-question="Go to upload documents">Upload documents</button>
                <button class="chatbot-quick-btn" data-question="What is the three-ring defense?">Three-ring defense</button>
                <button class="chatbot-quick-btn" data-question="How do I read safety scores?">Safety scores</button>
                <button class="chatbot-quick-btn" data-question="What is VeriTrail?">VeriTrail</button>
            </div>
        </div>
    </div>

    {{-- Input --}}
    <div class="px-3 py-2 border-top">
        <form id="chatbotForm" class="d-flex gap-2 align-items-center">
            <button type="button" class="chatbot-mic-btn" id="chatbotMicBtn" title="Voice input">
                <iconify-icon icon="iconamoon:microphone-duotone" class="fs-18"></iconify-icon>
            </button>
            <input type="text" class="form-control form-control-sm" id="chatbotInput" placeholder="Ask or say 'go to...'" autocomplete="off">
            <button type="submit" class="btn btn-sm btn-primary">
                <iconify-icon icon="iconamoon:send-duotone"></iconify-icon>
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var fab = document.getElementById('chatbotFab');
    var panel = document.getElementById('chatbotPanel');
    var closeBtn = document.getElementById('chatbotClose');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');
    var messages = document.getElementById('chatbotMessages');
    var fabIcon = document.getElementById('chatbotFabIcon');
    var micBtn = document.getElementById('chatbotMicBtn');
    var speakerToggle = document.getElementById('chatbotSpeakerToggle');
    var speakerIcon = document.getElementById('chatbotSpeakerIcon');
    var voiceEnabled = true;

    // ── Navigation commands (agent mode) ──
    var navCommands = [
        { patterns: ['ask question', 'ask a question', 'new question', 'query', 'go to ask', 'go to query', 'go to questions', 'ask something'],
          url: '{{ route("query.index") }}', label: 'Ask Question', desc: 'Opens the conversation interface to ask grounded questions.' },
        { patterns: ['upload', 'upload document', 'upload documents', 'upload file', 'upload files', 'go to upload', 'add document', 'add a document'],
          url: '{{ route("documents.create") }}', label: 'Upload Documents', desc: 'Opens the multi-file drag & drop upload page.' },
        { patterns: ['documents', 'go to documents', 'view documents', 'my documents', 'document list', 'files'],
          url: '{{ route("documents.index") }}', label: 'Documents', desc: 'Opens the document library with all your uploaded files.' },
        { patterns: ['dashboard', 'go to dashboard', 'home', 'go home', 'main page'],
          url: '{{ route("dashboard") }}', label: 'Dashboard', desc: 'Opens the main dashboard overview.' },
        { patterns: ['analytics', 'performance', 'go to analytics', 'go to performance', 'stats', 'statistics'],
          url: '{{ route("analytics") }}', label: 'Analytics', desc: 'Opens the analytics and performance dashboard.' },
        { patterns: ['audit', 'audit log', 'go to audit', 'go to audit log', 'logs', 'activity log'],
          url: '{{ route("audit-log") }}', label: 'Audit Log', desc: 'Opens the system audit trail with agent pipeline details.' },
        { patterns: ['ragas', 'evaluation', 'go to ragas', 'go to evaluation', 'metrics', 'ragas metrics'],
          url: '{{ route("evaluation") }}', label: 'RAGAS Evaluation', desc: 'Opens the RAGAS evaluation metrics page.' },
        { patterns: ['settings', 'go to settings', 'config', 'configuration', 'system settings', 'domains'],
          url: '{{ route("settings") }}', label: 'Settings', desc: 'Opens the system settings and domain management.' },
        { patterns: ['responsible ai', 'go to responsible ai', 'responsible', 'ethics', 'ai ethics'],
          url: '{{ route("responsible-ai") }}', label: 'Responsible AI', desc: 'Opens the Responsible AI commitment page.' },
        { patterns: ['agents', 'agent pipeline', 'go to agents', 'pipeline monitoring', 'pipeline'],
          url: '{{ route("agents") }}', label: 'Agent Pipeline', desc: 'Opens the agent pipeline monitoring dashboard.' },
        { patterns: ['profile', 'my profile', 'go to profile', 'account', 'my account'],
          url: '{{ route("profile.edit") }}', label: 'Profile', desc: 'Opens your profile settings.' },
        { patterns: ['safety test', 'safety tests', 'test safety', 'adversarial', 'go to safety test', 'run safety tests', 'synthetic test'],
          url: '{{ route("safety-test") }}', label: 'Safety Tests', desc: 'Opens the synthetic safety test suite to run adversarial prompts against Content Safety.' },
    ];

    // ── Knowledge base ──
    var knowledge = {
        'how do i ask a question': {
            answer: 'Go to <strong>Ask Question</strong> from the sidebar. Select a domain, type your question, and click Send. The system runs the full RAG pipeline with safety screening, retrieval, generation, and verification.'
        },
        'how do i upload documents': {
            answer: 'Go to <strong>Documents</strong> then click <strong>Upload Document</strong>. You can drag & drop up to 10 files at once. Select the domain they belong to. Supported formats include PDF, DOCX, XLSX, TXT, images, and more.'
        },
        'what is the three-ring defense': {
            answer: 'The <strong>Three-Ring Hallucination Defense</strong> verifies every answer:<br><br><strong>Ring 1 (50%)</strong>: Azure Groundedness - checks if the answer is supported by documents.<br><strong>Ring 2 (30%)</strong>: LettuceDetect NLI - decomposes claims and verifies each one.<br><strong>Ring 3 (20%)</strong>: SRLM Confidence - the model evaluates its own certainty.<br><br>Composite score: Green (75%+), Yellow (45-74%), Red (<45%).'
        },
        'how do i read safety scores': {
            answer: 'Each answer has a <strong>safety level</strong>:<br><br><span style="color:green">Green (75%+)</span>: Fully grounded in source docs.<br><span style="color:orange">Yellow (45-74%)</span>: Partially grounded, review needed.<br><span style="color:red">Red (<45%)</span>: Blocked - too much hallucination risk.<br><br>Click <strong>Safety Scores</strong> on any answer for the full breakdown.'
        },
        'what is veritrial': {
            answer: 'VeriTrail is Axiomeer\'s <strong>provenance tracing system</strong>. It creates a Directed Acyclic Graph (DAG) showing every pipeline step. You can trace each claim back to its source document, see support/unsupported verdicts, and identify error origins.'
        },
        'what are ragas metrics': {
            answer: '<strong>RAGAS</strong> measures 4 aspects:<br><br><strong>Faithfulness</strong>: Are claims supported by context?<br><strong>Answer Relevancy</strong>: Is the answer relevant to the question?<br><strong>Context Precision</strong>: Were retrieved chunks relevant?<br><strong>Context Recall</strong>: Were all relevant docs found?'
        },
        'how do domains work': {
            answer: 'Domains scope your knowledge base. Each document belongs to a domain (e.g., Legal, Healthcare, Finance). When you ask a question, the system searches only within the selected domain. Admins can add new domains in <strong>Settings</strong>.'
        },
        'what is web verify': {
            answer: 'Web Verify cross-references AI answers against live web sources using the Bing Search API. Click the <strong>Web Verify</strong> button on any chat response to see supporting or contradicting web results. This helps validate claims beyond just the uploaded documents.'
        },
        'what is the pipeline': {
            answer: 'The agent pipeline processes each query through 4 stages:<br><br><strong>1. Content Safety</strong>: Screens for harmful content and prompt injection.<br><strong>2. Retrieval</strong>: Finds relevant document chunks from Azure AI Search.<br><strong>3. Generation</strong>: Creates a grounded answer using Azure OpenAI.<br><strong>4. Verification</strong>: Runs the three-ring hallucination defense.<br><br>Each agent has its own OpenTelemetry trace for full observability.'
        }
    };

    // ── Toggle panel ──
    fab.addEventListener('click', function () {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            fabIcon.setAttribute('icon', 'iconamoon:sign-times-duotone');
            input.focus();
        } else {
            fabIcon.setAttribute('icon', 'iconamoon:comment-dots-duotone');
        }
    });

    closeBtn.addEventListener('click', function () {
        panel.classList.remove('open');
        fabIcon.setAttribute('icon', 'iconamoon:comment-dots-duotone');
    });

    // ── Speaker toggle ──
    speakerToggle.addEventListener('click', function () {
        voiceEnabled = !voiceEnabled;
        speakerIcon.setAttribute('icon', voiceEnabled ? 'iconamoon:volume-up-duotone' : 'iconamoon:volume-off-duotone');
        if (!voiceEnabled) window.speechSynthesis.cancel();
    });

    // ── Voice input (browser SpeechRecognition) ──
    var recognition = null;
    var recognizing = false;
    if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.lang = 'en-US';

        recognition.onstart = function () {
            recognizing = true;
            micBtn.classList.add('recording');
        };
        recognition.onresult = function (e) {
            var transcript = '';
            for (var i = e.resultIndex; i < e.results.length; i++) {
                transcript += e.results[i][0].transcript;
            }
            input.value = transcript;
        };
        recognition.onend = function () {
            recognizing = false;
            micBtn.classList.remove('recording');
            // Auto-submit if we got text
            if (input.value.trim()) {
                handleQuestion(input.value.trim());
                input.value = '';
            }
        };
        recognition.onerror = function () {
            recognizing = false;
            micBtn.classList.remove('recording');
        };
    }

    micBtn.addEventListener('click', function () {
        if (!recognition) {
            addMessage('Voice input is not supported in your browser. Try Chrome or Edge.', 'bot');
            return;
        }
        if (recognizing) recognition.stop();
        else recognition.start();
    });

    // ── Quick action buttons ──
    messages.addEventListener('click', function (e) {
        var btn = e.target.closest('.chatbot-quick-btn');
        if (btn) handleQuestion(btn.getAttribute('data-question'));
    });

    // ── Form submit ──
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var q = input.value.trim();
        if (!q) return;
        handleQuestion(q);
        input.value = '';
    });

    // ── Main handler ──
    function handleQuestion(question) {
        addMessage(question, 'user');

        var lower = question.toLowerCase().replace(/[?!.,]/g, '').trim();

        // 1. Check for navigation commands
        var navMatch = findNavigation(lower);
        if (navMatch) {
            setTimeout(function () {
                var html = navMatch.desc;
                html += '<a href="' + navMatch.url + '" class="chatbot-nav-action">';
                html += '<iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>';
                html += 'Go to ' + navMatch.label;
                html += '</a>';
                addMessage(html, 'bot');
                speakText('Taking you to ' + navMatch.label);

                // Auto-navigate after a brief delay
                setTimeout(function () { window.location.href = navMatch.url; }, 1500);
            }, 300);
            return;
        }

        // 2. Check knowledge base
        var kbMatch = findKnowledge(lower);
        if (kbMatch) {
            setTimeout(function () {
                addMessage(kbMatch.answer, 'bot');
                speakText(stripHtml(kbMatch.answer));
            }, 400);
            return;
        }

        // 3. Fallback - show available pages
        setTimeout(function () {
            var pages = [
                { name: 'Ask Question', url: '{{ route("query.index") }}' },
                { name: 'Documents', url: '{{ route("documents.index") }}' },
                { name: 'Upload Files', url: '{{ route("documents.create") }}' },
                { name: 'Analytics', url: '{{ route("analytics") }}' },
                { name: 'Audit Log', url: '{{ route("audit-log") }}' },
                { name: 'RAGAS Metrics', url: '{{ route("evaluation") }}' },
                { name: 'Settings', url: '{{ route("settings") }}' },
                { name: 'Agents', url: '{{ route("agents") }}' },
            ];
            var html = 'I\'m not sure about that. You can navigate to any page by saying <em>"go to [page]"</em>.<br><br>';
            html += '<strong>Available pages:</strong><br>';
            pages.forEach(function (p) {
                html += '<a href="' + p.url + '" class="chatbot-nav-action me-1 mb-1"><iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>' + p.name + '</a>';
            });
            html += '<br><br>Or ask about: safety scores, three-ring defense, VeriTrail, RAGAS, web verify, domains, or the pipeline.';
            addMessage(html, 'bot');
            speakText('I\'m not sure about that. Try saying go to followed by a page name, or ask about a feature.');
        }, 400);
    }

    // ── Navigation matching ──
    function findNavigation(text) {
        // Check direct pattern matches
        for (var i = 0; i < navCommands.length; i++) {
            var cmd = navCommands[i];
            for (var j = 0; j < cmd.patterns.length; j++) {
                if (text.indexOf(cmd.patterns[j]) !== -1) return cmd;
            }
        }

        // Check "go to X" / "take me to X" / "open X" / "navigate to X" patterns
        var goMatch = text.match(/(?:go to|take me to|open|navigate to|show me|show)\s+(.+)/);
        if (goMatch) {
            var target = goMatch[1].trim();
            for (var i = 0; i < navCommands.length; i++) {
                if (navCommands[i].label.toLowerCase().indexOf(target) !== -1) return navCommands[i];
                for (var j = 0; j < navCommands[i].patterns.length; j++) {
                    if (navCommands[i].patterns[j].indexOf(target) !== -1) return navCommands[i];
                }
            }
        }

        return null;
    }

    // ── Knowledge base matching ──
    function findKnowledge(text) {
        var bestMatch = null;
        var bestScore = 0;
        for (var key in knowledge) {
            var words = key.split(' ');
            var score = 0;
            words.forEach(function (w) {
                if (text.indexOf(w) !== -1) score++;
            });
            var pct = score / words.length;
            if (pct > bestScore) {
                bestScore = pct;
                bestMatch = knowledge[key];
            }
        }
        return bestScore >= 0.4 ? bestMatch : null;
    }

    // ── Add message to chat ──
    function addMessage(content, type) {
        var div = document.createElement('div');
        div.className = 'chatbot-msg chatbot-msg-' + type;
        div.innerHTML = '<div class="chatbot-bubble">' + (type === 'user' ? escapeHtml(content) : content) + '</div>';
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    // ── Text-to-speech (browser API) ──
    function speakText(text) {
        if (!voiceEnabled || !('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        var utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 1.05;
        utterance.pitch = 1;
        utterance.volume = 0.8;
        // Prefer a natural-sounding voice
        var voices = window.speechSynthesis.getVoices();
        var preferred = voices.find(function (v) {
            return v.lang.startsWith('en') && (v.name.indexOf('Natural') !== -1 || v.name.indexOf('Online') !== -1);
        }) || voices.find(function (v) { return v.lang.startsWith('en'); });
        if (preferred) utterance.voice = preferred;
        window.speechSynthesis.speak(utterance);
    }

    // Preload voices
    if ('speechSynthesis' in window) {
        window.speechSynthesis.getVoices();
        window.speechSynthesis.onvoiceschanged = function () { window.speechSynthesis.getVoices(); };
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function stripHtml(html) {
        var d = document.createElement('div');
        d.innerHTML = html;
        return d.textContent || d.innerText || '';
    }
});
</script>
