<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight Application Insights telemetry exporter.
 * Pushes custom events, metrics, and traces via the Track API.
 */
class TelemetryService
{
    private ?string $instrumentationKey;
    private ?string $ingestionEndpoint;
    private bool $enabled;

    public function __construct()
    {
        $connStr = config('azure.insights.connection_string', '');
        $this->instrumentationKey = null;
        $this->ingestionEndpoint = 'https://dc.services.visualstudio.com';
        $this->enabled = false;

        if ($connStr) {
            // Parse connection string: InstrumentationKey=xxx;IngestionEndpoint=https://...
            foreach (explode(';', $connStr) as $part) {
                $kv = explode('=', $part, 2);
                if (count($kv) === 2) {
                    $key = trim($kv[0]);
                    $val = trim($kv[1]);
                    if ($key === 'InstrumentationKey') $this->instrumentationKey = $val;
                    if ($key === 'IngestionEndpoint') $this->ingestionEndpoint = rtrim($val, '/');
                }
            }
            $this->enabled = !empty($this->instrumentationKey);
        }
    }

    public function isConfigured(): bool
    {
        return $this->enabled;
    }

    /**
     * Track a custom event (e.g., query processed, safety check completed).
     */
    public function trackEvent(string $name, array $properties = [], array $measurements = []): void
    {
        $this->send('Event', [
            'name' => $name,
            'properties' => $properties ?: null,
            'measurements' => $measurements ?: null,
        ]);
    }

    /**
     * Track a dependency call (Azure service call with latency).
     */
    public function trackDependency(string $name, string $type, string $target, int $durationMs, bool $success, array $properties = []): void
    {
        $this->send('RemoteDependency', [
            'name' => $name,
            'type' => $type,
            'target' => $target,
            'duration' => $this->formatDuration($durationMs),
            'success' => $success,
            'data' => $name,
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * Track a request (pipeline execution with full trace).
     */
    public function trackRequest(string $name, string $traceId, int $durationMs, bool $success, array $properties = []): void
    {
        $this->send('Request', [
            'id' => $traceId,
            'name' => $name,
            'duration' => $this->formatDuration($durationMs),
            'success' => $success,
            'responseCode' => $success ? '200' : '500',
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * Track a metric value.
     */
    public function trackMetric(string $name, float $value, array $properties = []): void
    {
        $this->send('Metric', [
            'metrics' => [['name' => $name, 'value' => $value, 'kind' => 'Measurement']],
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * Export a full pipeline trace (all agents) as a batch.
     */
    public function exportPipelineTrace(
        string $traceId,
        string $question,
        string $domainName,
        int $totalLatencyMs,
        string $safetyLevel,
        float $compositeScore,
        array $agentRuns
    ): void {
        if (!$this->enabled) return;

        // Track the overall request
        $this->trackRequest('RAG Pipeline', $traceId, $totalLatencyMs, $safetyLevel !== 'red', [
            'domain' => $domainName,
            'safety_level' => $safetyLevel,
            'composite_score' => (string) $compositeScore,
            'question_length' => (string) strlen($question),
        ]);

        // Track each agent as a dependency
        foreach ($agentRuns as $run) {
            $this->trackDependency(
                ucfirst(str_replace('_', ' ', $run['agent_type'] ?? 'unknown')),
                'Azure AI',
                $run['agent_type'] ?? 'unknown',
                $run['latency_ms'] ?? 0,
                ($run['status'] ?? '') === 'completed',
                [
                    'trace_id' => $traceId,
                    'span_id' => $run['span_id'] ?? '',
                    'token_count' => (string) ($run['token_count'] ?? 0),
                ]
            );
        }

        // Track safety metrics
        $this->trackMetric('composite_safety_score', $compositeScore, ['domain' => $domainName]);
        $this->trackMetric('pipeline_latency_ms', $totalLatencyMs, ['domain' => $domainName]);
    }

    private function send(string $telemetryType, array $data): void
    {
        if (!$this->enabled) return;

        $envelope = [
            'name' => "Microsoft.ApplicationInsights.{$this->instrumentationKey}.{$telemetryType}",
            'time' => now()->toIso8601String(),
            'iKey' => $this->instrumentationKey,
            'data' => [
                'baseType' => "{$telemetryType}Data",
                'baseData' => array_filter($data, fn ($v) => $v !== null),
            ],
        ];

        try {
            Http::timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->ingestionEndpoint}/v2/track", $envelope);
        } catch (\Throwable $e) {
            Log::debug('App Insights telemetry send failed', ['error' => $e->getMessage()]);
        }
    }

    private function formatDuration(int $ms): string
    {
        $s = intdiv($ms, 1000);
        $ms = $ms % 1000;
        $m = intdiv($s, 60);
        $s = $s % 60;
        $h = intdiv($m, 60);
        $m = $m % 60;
        return sprintf('%02d:%02d:%02d.%03d0000', $h, $m, $s, $ms);
    }
}
