<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Query;
use App\Services\RAGPipelineService;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    public function __construct(
        private RAGPipelineService $pipeline,
    ) {}

    public function index()
    {
        $queries = Query::with(['domain', 'user'])
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        $domains = Domain::where('is_active', true)->get();

        return view('query.index', compact('queries', 'domains'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:2000',
            'domain_id' => 'required|exists:domains,id',
        ]);

        $query = Query::create([
            'user_id' => auth()->id(),
            'domain_id' => $request->domain_id,
            'question' => $request->question,
            'status' => 'pending',
        ]);

        // Run the RAG pipeline synchronously (demo mode).
        // In production, this would be dispatched as a queued job.
        $query = $this->pipeline->process($query);

        $flashKey = $query->status === 'completed' ? 'success' : 'error';
        $flashMsg = $query->status === 'completed'
            ? 'Your question has been answered.'
            : 'Processing encountered an issue. See details below.';

        return redirect()->route('query.show', $query)
            ->with($flashKey, $flashMsg);
    }

    public function show(Query $query)
    {
        $this->authorizeView($query);

        $query->load(['domain', 'user', 'citations.document', 'agentRuns']);

        return view('query.show', compact('query'));
    }

    private function authorizeView(Query $query): void
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && $query->user_id !== $user->id) {
            abort(403);
        }
    }
}
