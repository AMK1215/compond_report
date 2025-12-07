<?php

namespace App\Http\Controllers\Api\Buffalo;

use App\Http\Controllers\Controller;
use App\Models\BuffaloProviderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewBuffaloAdminController extends Controller
{
    public function updateRtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rtp' => ['required', 'numeric', 'min:0', 'max:100'],
            'sign' => ['nullable', 'string'],
            'requestTime' => ['required'],
        ]);

        $configCheck = $this->validateConfig();
        if ($configCheck !== true) {
            return $configCheck;
        }

        $generatedSign = md5('update_rtp'.$data['requestTime'].config('new_buffalo_key.buffalo_secret_key'));

        if (! empty($data['sign']) && ! hash_equals(strtolower($data['sign']), $generatedSign)) {
            Log::warning('New Buffalo admin update RTP: provided signature mismatch', [
                'provided' => $data['sign'],
                'expected' => $generatedSign,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $payload = [
            'rtp' => $data['rtp'],
            'requestTime' => $data['requestTime'],
            'sign' => $generatedSign,
        ];

        $providerResponse = $this->callProviderEndpoint('/ws/rtp', $payload);
        if ($providerResponse instanceof JsonResponse) {
            return $providerResponse;
        }

        $setting = BuffaloProviderSetting::getSingleton();
        $setting->update([
            'rtp' => $providerResponse['data']['rtp'] ?? $data['rtp'],
            'rtp_request_time' => $data['requestTime'],
        ]);

        return response()->json([
            'data' => [
                'rtp' => (float) $setting->rtp,
            ],
            'message' => 'Success',
        ]);
    }

    public function updateServerMaintenance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'boolean'],
            'reason' => ['nullable', 'string'],
            'sign' => ['nullable', 'string'],
            'requestTime' => ['required'],
        ]);

        $configCheck = $this->validateConfig();
        if ($configCheck !== true) {
            return $configCheck;
        }

        $generatedSign = md5('server_maintenance'.$data['requestTime'].config('new_buffalo_key.buffalo_secret_key'));

        if (! empty($data['sign']) && ! hash_equals(strtolower($data['sign']), $generatedSign)) {
            Log::warning('New Buffalo admin server maintenance: provided signature mismatch', [
                'provided' => $data['sign'],
                'expected' => $generatedSign,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $payload = [
            'status' => $data['status'],
            'reason' => $data['reason'] ?? null,
            'requestTime' => $data['requestTime'],
            'sign' => $generatedSign,
        ];

        $providerResponse = $this->callProviderEndpoint('/ws/server-maintain', $payload);
        if ($providerResponse instanceof JsonResponse) {
            return $providerResponse;
        }

        $setting = BuffaloProviderSetting::getSingleton();
        $setting->update([
            'is_under_maintenance' => $data['status'],
            'maintenance_reason' => $data['status'] ? ($data['reason'] ?? null) : null,
            'maintenance_request_time' => $data['requestTime'],
        ]);

        return response()->json([
            'data' => [
                'status' => $setting->is_under_maintenance,
                'reason' => $setting->maintenance_reason,
            ],
            'message' => 'Success',
        ]);
    }

    public function resetServer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sign' => ['nullable', 'string'],
            'requestTime' => ['required'],
        ]);

        $configCheck = $this->validateConfig();
        if ($configCheck !== true) {
            return $configCheck;
        }

        $generatedSign = md5('reset_server'.$data['requestTime'].config('new_buffalo_key.buffalo_secret_key'));

        if (! empty($data['sign']) && ! hash_equals(strtolower($data['sign']), $generatedSign)) {
            Log::warning('New Buffalo admin reset server: provided signature mismatch', [
                'provided' => $data['sign'],
                'expected' => $generatedSign,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $payload = [
            'requestTime' => $data['requestTime'],
            'sign' => $generatedSign,
        ];

        $providerResponse = $this->callProviderEndpoint('/ws/reset-server', $payload);
        if ($providerResponse instanceof JsonResponse) {
            return $providerResponse;
        }

        $setting = BuffaloProviderSetting::getSingleton();
        $setting->update([
            'rtp' => 0,
            'rtp_request_time' => null,
            'total_bet_amount' => 0,
            'total_prize_amount' => 0,
            'total_profit' => 0,
            'spin_count' => 0,
        ]);

        return response()->json([
            'data' => [
                'rtp' => (float) $setting->rtp,
                'total_bet_amount' => (float) $setting->total_bet_amount,
                'total_prize_amount' => (float) $setting->total_prize_amount,
                'total_profit' => (float) $setting->total_profit,
                'spin_count' => $setting->spin_count,
            ],
            'message' => 'Success',
        ]);
    }

    private function isValidSignature(string $action, $requestTime, string $sign): bool
    {
        $secretKey = config('new_buffalo_key.buffalo_secret_key');

        if (empty($secretKey)) {
            Log::error('New Buffalo admin API: secret key is not configured');

            return false;
        }

        $expected = md5($action . $requestTime . $secretKey);

        return hash_equals($expected, strtolower($sign));
    }

    private function validateConfig(): JsonResponse|bool
    {
        $apiBase = rtrim(config('new_buffalo_key.buffalo_api_url', ''), '/');
        $secretKey = config('new_buffalo_key.buffalo_secret_key');

        if (empty($apiBase) || empty($secretKey)) {
            Log::error('New Buffalo admin API: configuration missing', [
                'api_base' => $apiBase,
                'secret_key_present' => ! empty($secretKey),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Configuration error',
            ], 500);
        }

        return true;
    }

    private function callProviderEndpoint(string $path, array $payload): JsonResponse|array
    {
        $apiBase = rtrim(config('new_buffalo_key.buffalo_api_url', ''), '/');

        try {
            $response = Http::timeout(10)->post($apiBase.$path, $payload);
        } catch (\Throwable $e) {
            Log::error('New Buffalo admin API: provider call failed', [
                'endpoint' => $apiBase.$path,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Provider unavailable',
            ], 502);
        }

        if (! $response->successful()) {
            Log::warning('New Buffalo admin API: provider error', [
                'endpoint' => $apiBase.$path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Provider error',
                'provider_status' => $response->status(),
                'provider_response' => $response->json(),
            ], $response->status());
        }

        $decoded = $response->json();

        if (\is_null($decoded) && trim($response->body()) === '') {
            return [];
        }

        if (! is_array($decoded)) {
            Log::warning('New Buffalo admin API: provider non-JSON response', [
                'endpoint' => $apiBase.$path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid provider response',
                'provider_status' => $response->status(),
                'provider_response' => $response->body(),
            ], 502);
        }

        return $decoded;
    }
}

