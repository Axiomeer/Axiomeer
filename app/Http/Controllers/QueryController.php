<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Domain;
use App\Models\Query;
use App\Services\RAGPipelineService;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct(
        private RAGPipelineService $pipeline,
    ) {}

    public function index(Request $request)
    {
        $conversations = Conversation::with(['domain', 'queries' => fn ($q) => $q->latest()->limit(1)])
            ->where('user_id', auth()->id())
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhereHas('queries', fn ($qq) => $qq->where('question', 'like', '%' . $request->search . '%'));
            })
            ->orderByDesc('last_activity_at')
            ->paginate(20);

        $domains = Domain::with(['documents'])->where('is_active', true)->get();

        // Also get orphan queries (pre-conversation era)
        $orphanQueries = Query::with(['domain'])
            ->where('user_id', auth()->id())
            ->whereNull('conversation_id')
            ->latest()
            ->limit(10)
            ->get();

        return view('query.index', compact('conversations', 'domains', 'orphanQueries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:2000',
            'domain_id' => 'required|exists:domains,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'string',
        ]);

        // Find or create conversation
        $conversationId = $request->conversation_id;

        if (!$conversationId) {
            $conversation = Conversation::create([
                'user_id' => auth()->id(),
                'domain_id' => $request->domain_id,
                'title' => null, // Will be AI-generated after first answer
                'last_activity_at' => now(),
            ]);
            $conversationId = $conversation->id;
        } else {
            $conversation = Conversation::findOrFail($conversationId);
            if ($conversation->user_id !== auth()->id()) {
                abort(403);
            }
            $conversation->update(['last_activity_at' => now()]);
        }

        $query = Query::create([
            'user_id' => auth()->id(),
            'domain_id' => $request->domain_id,
            'conversation_id' => $conversationId,
            'question' => $request->question,
            'status' => 'pending',
        ]);

        // Run the RAG pipeline synchronously
        $documentIds = array_filter($request->input('document_ids', []));
        $query = $this->pipeline->process($query, $documentIds);

        // Auto-generate conversation title from first query
        if (!$conversation->title && $query->status === 'completed') {
            $conversation->update([
                'title' => $this->generateTitle($query->question, $query->answer),
            ]);
        }

        return redirect()->route('query.show', $query);
    }

    public function show(Query $query)
    {
        $this->authorizeView($query);

        $query->load(['domain', 'user', 'citations.document', 'agentRuns', 'evaluationMetrics']);

        // Load conversation with all its queries for the chat thread
        $conversation = null;
        $conversationQueries = collect();
        if ($query->conversation_id) {
            $conversation = $query->conversation;
            $conversationQueries = Query::with(['domain', 'user', 'citations.document', 'agentRuns', 'evaluationMetrics'])
                ->where('conversation_id', $query->conversation_id)
                ->orderBy('created_at')
                ->get();
        }

        $domains = Domain::where('is_active', true)->get();

        return view('query.show', compact('query', 'conversation', 'conversationQueries', 'domains'));
    }

    private function authorizeView(Query $query): void
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && $query->user_id !== $user->id) {
            abort(403);
        }
    }

    private function generateTitle(string $question, ?string $answer): string
    {
        // Simple title generation - take first meaningful part of the question
        $title = $question;

        // Remove common question starters
        $starters = ['tell me about', 'what is', 'what are', 'how do', 'how does', 'can you explain', 'explain', 'describe', 'what does'];
        foreach ($starters as $starter) {
            if (stripos($title, $starter) === 0) {
                $title = trim(substr($title, strlen($starter)));
                break;
            }
        }

        // Capitalize and truncate
        return ucfirst(\Illuminate\Support\Str::limit($title, 60));
    }
}
