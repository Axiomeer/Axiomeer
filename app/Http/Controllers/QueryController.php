<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Query;
use Illuminate\Http\Request;

class QueryController extends Controller
{
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

        // In a real implementation, this would dispatch a job to the
        // Azure AI agent pipeline. For now, we simulate a pending state.

        return redirect()->route('query.show', $query)
            ->with('success', 'Your question has been submitted and is being processed.');
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
