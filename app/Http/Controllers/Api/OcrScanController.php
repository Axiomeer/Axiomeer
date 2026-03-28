<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OcrScanController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['image' => 'required|string']); // base64

        $key    = config('azure.vision.api_key');
        $endpoint = rtrim(config('azure.vision.endpoint'), '/');

        if (empty($key) || empty($endpoint)) {
            return response()->json(['error' => 'Vision service not configured'], 503);
        }

        // Decode base64 — strip data-uri prefix if present
        $imageData = $request->input('image');
        if (str_contains($imageData, ',')) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }
        $binary = base64_decode($imageData);

        // Azure AI Vision Read API — GA version 2024-02-01
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $key,
        ])->timeout(20)->withBody($binary, 'application/octet-stream')->post(
            "{$endpoint}/computervision/imageanalysis:analyze?api-version=2024-02-01&features=read"
        );

        if ($response->failed()) {
            return response()->json([
                'error' => 'OCR request failed: HTTP ' . $response->status() . ' — ' . $response->body()
            ], 502);
        }

        $json = $response->json();
        // 2024-02-01 response structure: readResult.blocks[].lines[].text
        $lines = [];
        foreach (data_get($json, 'readResult.blocks', []) as $block) {
            foreach (data_get($block, 'lines', []) as $line) {
                $lines[] = $line['text'] ?? ($line['content'] ?? '');
            }
        }
        $text = implode("\n", $lines);

        return response()->json(['text' => $text, 'line_count' => count($lines)]);
    }
}
