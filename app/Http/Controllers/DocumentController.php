<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Domain;
use App\Services\Azure\AzureSearchService;
use App\Services\Azure\DocumentIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(
        private DocumentIntelligenceService $docIntelligence,
        private AzureSearchService $searchService,
    ) {}
    public function index(Request $request)
    {
        $query = Document::with(['domain', 'uploader'])
            ->orderByDesc('created_at');

        if ($request->filled('domain')) {
            $query->where('domain_id', $request->domain);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $documents = $query->paginate(15)->withQueryString();
        $domains = Domain::where('is_active', true)->get();

        return view('documents.index', compact('documents', 'domains'));
    }

    public function create()
    {
        $domains = Domain::where('is_active', true)->get();
        return view('documents.create', compact('domains'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'domain_id' => 'required|exists:domains,id',
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,txt,csv,json,jpg,jpeg,png,bmp,tiff,heif,xlsx,pptx,html',
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('documents', $filename, 'local');

        $document = Document::create([
            'domain_id' => $request->domain_id,
            'uploaded_by' => auth()->id(),
            'title' => $request->title,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'storage_path' => $path,
            'status' => 'indexing',
        ]);

        // Parse document with Azure Document Intelligence
        $analysis = $this->docIntelligence->analyzeDocument($path);

        if ($analysis['success']) {
            $chunks = $analysis['chunks'] ?? [];
            $domain = Domain::find($request->domain_id);

            // Push chunks to Azure AI Search index
            $indexResult = $this->searchService->indexDocumentChunks(
                (string) $document->id,
                $request->title,
                $domain->slug ?? 'general',
                $chunks,
            );

            $document->update([
                'status' => 'indexed',
                'chunk_count' => $analysis['chunk_count'] ?? 0,
                'index_name' => config('azure.search.index'),
                'indexed_at' => now(),
                'metadata' => [
                    'page_count' => $analysis['page_count'] ?? 0,
                    'tables_found' => $analysis['tables_found'] ?? 0,
                    'paragraphs_found' => $analysis['paragraphs_found'] ?? 0,
                    'chunks_indexed' => $indexResult['indexed'] ?? 0,
                    'mock_parse' => $analysis['mock'] ?? false,
                    'mock_index' => $indexResult['mock'] ?? false,
                ],
            ]);
        } else {
            $document->update([
                'status' => 'failed',
                'metadata' => ['error' => $analysis['error'] ?? 'Unknown parsing error'],
            ]);
        }

        return redirect()->route('documents.index')
            ->with('success', "Document \"{$document->title}\" uploaded and processed ({$document->chunk_count} chunks).");
    }

    public function show(Document $document)
    {
        $document->load(['domain', 'uploader', 'citations.relatedQuery']);
        return view('documents.show', compact('document'));
    }

    public function destroy(Document $document)
    {
        // Remove chunks from search index
        if ($document->chunk_count > 0) {
            $this->searchService->removeDocument((string) $document->id, $document->chunk_count);
        }

        Storage::disk('local')->delete($document->storage_path);
        $title = $document->title;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Document \"{$title}\" deleted.");
    }
}
