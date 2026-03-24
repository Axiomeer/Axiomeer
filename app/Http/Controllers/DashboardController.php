<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Query;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalQueries = Query::count();
        $documentsIndexed = Document::where('status', 'indexed')->count();
        $totalDocuments = Document::count();

        $avgFaithfulness = Query::whereNotNull('groundedness_score')
            ->avg('groundedness_score');

        $hallucinationsBlocked = Query::where('safety_level', 'red')->count();

        $recentQueries = Query::with('domain')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalQueries',
            'documentsIndexed',
            'totalDocuments',
            'avgFaithfulness',
            'hallucinationsBlocked',
            'recentQueries'
        ));
    }
}
