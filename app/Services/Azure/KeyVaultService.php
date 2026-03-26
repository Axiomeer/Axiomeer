<?php

namespace App\Services\Azure;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KeyVaultService
{
    private string $vaultUri;
    private string $apiVersion = '7.4';
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->vaultUri = rtrim(config('azure.key_vault.uri', ''), '/');
        $this->apiVersion = config('azure.key_vault.api_version', '7.4');
    }

    /**
     * Retrieve a secret value from Key Vault (cached for 10 minutes).
     */
    public function getSecret(string $secretName): ?string
    {
        $cacheKey = "keyvault_secret_{$secretName}";

        return Cache::remember($cacheKey, 600, function () use ($secretName) {
            try {
                $token = $this->getAccessToken();
                if (! $token) {
                    return null;
                }

                $url = "{$this->vaultUri}/secrets/{$secretName}?api-version={$this->apiVersion}";

                $response = Http::withToken($token)
                    ->timeout(10)
                    ->get($url);

                if ($response->successful()) {
                    return $response->json('value');
                }

                Log::warning('KeyVault secret fetch failed', [
                    'secret' => $secretName,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Throwable $e) {
                Log::error('KeyVault exception', ['secret' => $secretName, 'error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Set or update a secret in Key Vault.
     */
    public function setSecret(string $secretName, string $value): bool
    {
        try {
            $token = $this->getAccessToken();
            if (! $token) {
                return false;
            }

            $url = "{$this->vaultUri}/secrets/{$secretName}?api-version={$this->apiVersion}";

            $response = Http::withToken($token)
                ->timeout(15)
                ->put($url, ['value' => $value]);

            if ($response->successful()) {
                Cache::forget("keyvault_secret_{$secretName}");
                return true;
            }

            Log::warning('KeyVault secret set failed', [
                'secret' => $secretName,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('KeyVault set exception', ['secret' => $secretName, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get all secret names from the vault.
     */
    public function listSecrets(): array
    {
        try {
            $token = $this->getAccessToken();
            if (! $token) {
                return [];
            }

            $url = "{$this->vaultUri}/secrets?api-version={$this->apiVersion}";

            $response = Http::withToken($token)->timeout(10)->get($url);

            if ($response->successful()) {
                return collect($response->json('value', []))
                    ->map(fn ($s) => basename(parse_url($s['id'], PHP_URL_PATH)))
                    ->values()
                    ->toArray();
            }

            return [];
        } catch (\Throwable $e) {
            Log::error('KeyVault list exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtain an Azure AD access token for Key Vault using the Managed Identity
     * or falls back to Client Credentials flow (client_id/client_secret in env).
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $cacheKey = 'keyvault_access_token';
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $this->accessToken = $cached;
            return $cached;
        }

        // Try IMDS (Managed Identity) first
        try {
            $imdsResponse = Http::timeout(3)->withHeaders([
                'Metadata' => 'true',
            ])->get('http://169.254.169.254/metadata/identity/oauth2/token', [
                'api-version' => '2018-02-01',
                'resource' => 'https://vault.azure.net',
            ]);

            if ($imdsResponse->successful()) {
                $token = $imdsResponse->json('access_token');
                $expiresIn = (int) $imdsResponse->json('expires_in', 3000);
                Cache::put($cacheKey, $token, $expiresIn - 60);
                $this->accessToken = $token;
                return $token;
            }
        } catch (\Throwable) {
            // IMDS not available (local dev), fall through to client credentials
        }

        // Client Credentials flow (local dev / SP auth)
        $tenantId = env('AZURE_TENANT_ID');
        $clientId = env('AZURE_CLIENT_ID');
        $clientSecret = env('AZURE_CLIENT_SECRET');

        if (! $tenantId || ! $clientId || ! $clientSecret) {
            Log::warning('KeyVault: no Managed Identity and no client credentials configured');
            return null;
        }

        try {
            $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

            $response = Http::asForm()->timeout(10)->post($tokenUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://vault.azure.net/.default',
            ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                $expiresIn = (int) $response->json('expires_in', 3000);
                Cache::put($cacheKey, $token, $expiresIn - 60);
                $this->accessToken = $token;
                return $token;
            }

            Log::warning('KeyVault token fetch failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::error('KeyVault token exception', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
