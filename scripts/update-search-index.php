<?php

/**
 * update-search-index.php
 *
 * Adds the content_vector field and HNSW vector search configuration
 * to the Axiomeer Azure AI Search index if they do not already exist.
 *
 * Usage: php scripts/update-search-index.php
 */

// ---------------------------------------------------------------------------
// Load .env values (simple parser — no Laravel bootstrap required)
// ---------------------------------------------------------------------------

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    fwrite(STDERR, "ERROR: .env file not found at {$envFile}\n");
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) {
        continue;
    }
    if (strpos($line, '=') === false) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $value = trim($value, '"\'');
    $env[trim($key)] = $value;
}

$searchEndpoint  = rtrim($env['AZURE_AI_SEARCH_ENDPOINT']  ?? '', '/');
$searchKey       = $env['AZURE_AI_SEARCH_KEY']             ?? '';
$indexName       = $env['AZURE_AI_SEARCH_INDEX']           ?? 'axiomeer-knowledge';
$apiVersion      = $env['AZURE_AI_SEARCH_API_VERSION']     ?? '2024-07-01';

if (empty($searchEndpoint) || empty($searchKey)) {
    fwrite(STDERR, "ERROR: AZURE_AI_SEARCH_ENDPOINT or AZURE_AI_SEARCH_KEY not set in .env\n");
    exit(1);
}

echo "Azure AI Search endpoint : {$searchEndpoint}\n";
echo "Index                    : {$indexName}\n";
echo "API version              : {$apiVersion}\n\n";

// ---------------------------------------------------------------------------
// Helper: execute an HTTP request via cURL
// ---------------------------------------------------------------------------

function curlRequest(string $method, string $url, string $apiKey, ?array $body = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $headers = [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
    ];

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Length: ' . strlen($json);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body'   => $responseBody,
        'error'  => $curlError,
    ];
}

// ---------------------------------------------------------------------------
// Step 1: GET current index definition
// ---------------------------------------------------------------------------

$getUrl = "{$searchEndpoint}/indexes/{$indexName}?api-version={$apiVersion}";

echo "Fetching current index definition...\n";
$getResult = curlRequest('GET', $getUrl, $searchKey);

if (!empty($getResult['error'])) {
    fwrite(STDERR, "ERROR: cURL error — {$getResult['error']}\n");
    exit(1);
}

if ($getResult['status'] !== 200) {
    fwrite(STDERR, "ERROR: GET index returned HTTP {$getResult['status']}\n");
    fwrite(STDERR, $getResult['body'] . "\n");
    exit(1);
}

$indexDef = json_decode($getResult['body'], true);
if ($indexDef === null) {
    fwrite(STDERR, "ERROR: Failed to parse index JSON\n");
    exit(1);
}

echo "Index retrieved successfully.\n\n";

// ---------------------------------------------------------------------------
// Step 2: Check if content_vector field already exists
// ---------------------------------------------------------------------------

$fields        = $indexDef['fields'] ?? [];
$fieldNames    = array_column($fields, 'name');
$vectorExists  = in_array('content_vector', $fieldNames, true);

if ($vectorExists) {
    echo "INFO: 'content_vector' field already exists in the index. Nothing to do.\n";
    exit(0);
}

echo "Adding 'content_vector' field and HNSW vector search configuration...\n";

// ---------------------------------------------------------------------------
// Step 3: Add the content_vector field to the fields array
// ---------------------------------------------------------------------------

$indexDef['fields'][] = [
    'name'                => 'content_vector',
    'type'                => 'Collection(Edm.Single)',
    'searchable'          => true,
    'filterable'          => false,
    'retrievable'         => true,
    'stored'              => true,
    'sortable'            => false,
    'facetable'           => false,
    'key'                 => false,
    'dimensions'          => 1536,
    'vectorSearchProfile' => 'axiomeer-vector-profile',
];

// ---------------------------------------------------------------------------
// Step 4: Add vectorSearch configuration
// ---------------------------------------------------------------------------

$indexDef['vectorSearch'] = [
    'profiles'   => [
        [
            'name'      => 'axiomeer-vector-profile',
            'algorithm' => 'axiomeer-hnsw',
        ],
    ],
    'algorithms' => [
        [
            'name'            => 'axiomeer-hnsw',
            'kind'            => 'hnsw',
            'hnswParameters'  => [
                'm'              => 4,
                'efConstruction' => 400,
                'efSearch'       => 500,
                'metric'         => 'cosine',
            ],
        ],
    ],
];

// ---------------------------------------------------------------------------
// Step 5: Remove read-only / system-managed properties that cannot be PUT back
// ---------------------------------------------------------------------------

unset($indexDef['@odata.context']);
unset($indexDef['@odata.etag']);

// ---------------------------------------------------------------------------
// Step 6: PUT the updated index definition
// ---------------------------------------------------------------------------

$putUrl = "{$searchEndpoint}/indexes/{$indexName}?api-version={$apiVersion}";

echo "Sending PUT request to update index...\n";
$putResult = curlRequest('PUT', $putUrl, $searchKey, $indexDef);

if (!empty($putResult['error'])) {
    fwrite(STDERR, "ERROR: cURL error on PUT — {$putResult['error']}\n");
    exit(1);
}

// 200 = updated, 201 = created, 204 = no content (success with no body)
if (in_array($putResult['status'], [200, 201, 204], true)) {
    echo "SUCCESS: Index updated — 'content_vector' field and HNSW vector search added.\n";
    echo "HTTP status: {$putResult['status']}\n";
    exit(0);
} else {
    fwrite(STDERR, "ERROR: PUT index returned HTTP {$putResult['status']}\n");
    fwrite(STDERR, $putResult['body'] . "\n");
    exit(1);
}
