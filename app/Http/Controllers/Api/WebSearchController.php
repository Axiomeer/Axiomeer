<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebSearchController extends Controller
{
    public function search(Request $request, WebSearchService $webSearch): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $results = $webSearch->search($request->query('query') ?? $request->input('query'), 5);

        return response()->json($results);
    }
}
