<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceBusService
{
    private string $namespace;
    private string $sasKeyName;
    private string $sasKey;
    private string $auditQueue;
    private string $verificationQueue;

    public function __construct()
    {
        $connection = config('azure.service_bus.connection', '');
        $parsed = $this->parseConnectionString($connection);

        $this->namespace         = $parsed['Endpoint'] ?? config('azure.service_bus.namespace', 'axiomeer-bus');
        $this->sasKeyName        = $parsed['SharedAccessKeyName'] ?? 'RootManageSharedAccessKey';
        $this->sasKey            = $parsed['SharedAccessKey'] ?? '';
        $this->auditQueue        = config('azure.service_bus.audit_queue', 'audit-events');
        $this->verificationQueue = config('azure.service_bus.verification_queue', 'verification-queue');
    }

    /**
     * Send an audit event to the audit-events queue asynchronously.
     */
    public function sendAuditEvent(array $event): bool
    {
        $payload = array_merge($event, [
            'sent_at'  => now()->toIso8601String(),
            'app'      => 'axiomeer',
        ]);

        return $this->sendMessage($this->auditQueue, $payload);
    }

    /**
     * Send a verification request to the verification queue.
     */
    public function sendVerificationRequest(array $payload): bool
    {
        return $this->sendMessage($this->verificationQueue, $payload);
    }

    /**
     * Send a message to a Service Bus queue using the REST API.
     */
    public function sendMessage(string $queue, array $payload): bool
    {
        try {
            $url   = "{$this->namespace}/{$queue}/messages";
            $token = $this->buildSasToken($url);
            $body  = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response = Http::withHeaders([
                'Authorization'  => $token,
                'Content-Type'   => 'application/json',
                'BrokerProperties' => json_encode([
                    'MessageId' => (string) \Illuminate\Support\Str::uuid(),
                    'Label'     => $queue,
                ]),
            ])->timeout(10)->post($url, $payload);

            if ($response->successful() || $response->status() === 201) {
                return true;
            }

            Log::warning('ServiceBus message send failed', [
                'queue'  => $queue,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('ServiceBus send exception', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Queue an audit log entry for the given action (fire-and-forget).
     */
    public function queueAuditLog(
        string $action,
        ?int $userId = null,
        array $metadata = []
    ): void {
        try {
            $this->sendAuditEvent([
                'action'    => $action,
                'user_id'   => $userId ?? auth()->id(),
                'ip'        => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'metadata'  => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ServiceBus queueAuditLog failed silently', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Build a SAS token for the given URL.
     */
    private function buildSasToken(string $url): string
    {
        $expiry = time() + 300; // 5 minutes
        $encodedUrl = rawurlencode(strtolower($url));
        $stringToSign = "{$encodedUrl}\n{$expiry}";
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->sasKey, true));
        $encodedSig = rawurlencode($signature);

        return "SharedAccessSignature sr={$encodedUrl}&sig={$encodedSig}&se={$expiry}&skn={$this->sasKeyName}";
    }

    /**
     * Parse a Service Bus connection string into key-value pairs.
     * Format: Endpoint=sb://...;SharedAccessKeyName=...;SharedAccessKey=...
     */
    private function parseConnectionString(string $connection): array
    {
        $result = [];
        foreach (explode(';', $connection) as $part) {
            $part = trim($part);
            if (! $part) {
                continue;
            }
            $eqPos = strpos($part, '=');
            if ($eqPos === false) {
                continue;
            }
            $key   = trim(substr($part, 0, $eqPos));
            $value = trim(substr($part, $eqPos + 1));

            // Normalize the Endpoint to an HTTPS URL for REST API calls
            if ($key === 'Endpoint') {
                $value = str_replace('sb://', 'https://', rtrim($value, '/'));
            }

            $result[$key] = $value;
        }
        return $result;
    }
}
