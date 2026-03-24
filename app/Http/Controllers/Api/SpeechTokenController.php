<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class SpeechTokenController extends Controller
{
    /**
     * Issue an Azure Speech token for client-side speech-to-text.
     */
    public function __invoke(): JsonResponse
    {
        $key = config('azure.speech.api_key');
        $region = config('azure.speech.region');

        if (empty($key) || empty($region)) {
            return response()->json(['error' => 'Speech service not configured'], 503);
        }

        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $key,
            'Content-Length' => 0,
        ])->post("https://{$region}.api.cognitive.microsoft.com/sts/v1.0/issueToken");

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to obtain speech token'], 502);
        }

        return response()->json([
            'token' => $response->body(),
            'region' => $region,
        ]);
    }
}
