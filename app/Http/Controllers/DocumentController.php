<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
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
            'file' => 'required|file|max:51200|mimes:pdf,doc,docx,txt,csv,json',
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
            'status' => 'pending',
        ]);

        return redirect()->route('documents.index')
            ->with('success', "Document \"{$document->title}\" uploaded successfully.");
    }

    public function show(Document $document)
    {
        $document->load(['domain', 'uploader', 'citations.query']);
        return view('documents.show', compact('document'));
    }

    public function destroy(Document $document)
    {
        Storage::disk('local')->delete($document->storage_path);
        $title = $document->title;
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', "Document \"{$title}\" deleted.");
    }
}
