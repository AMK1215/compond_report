<?php

namespace App\Http\Controllers\Admin\BuffaloGame;

use App\Http\Controllers\Controller;
use App\Models\BuffaloProviderSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BuffaloRtpController extends Controller
{
    public function index(Request $request): View
    {
        $setting = BuffaloProviderSetting::getSingleton();

        $currentRtp = (float) ($setting->rtp ?? 0);
        $lastUpdatedAt = null;

        if ($setting->rtp_request_time) {
            $timestamp = (int) $setting->rtp_request_time;

            $dateTime = $timestamp > 9999999999
                ? Carbon::createFromTimestampMs($timestamp)
                : Carbon::createFromTimestamp($timestamp);

            $lastUpdatedAt = $dateTime->setTimezone(config('app.timezone'));
        }

        return view('admin.buffalo_game.rtp', [
            'currentRtp' => $currentRtp,
            'lastUpdatedAt' => $lastUpdatedAt,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rtp' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $rtp = (float) $validated['rtp'];

        if ($rtp > 1) {
            $rtp = $rtp / 100;
        }

        $rtp = round($rtp, 4);

        $requestTime = now()->timestamp;
        $secretKey = config('new_buffalo_key.buffalo_secret_key');
        $apiBase = rtrim(config('new_buffalo_key.buffalo_api_url', ''), '/');

        if (empty($secretKey) || empty($apiBase)) {
            return back()
                ->withInput()
                ->with('error', 'Buffalo provider credentials are not configured.');
        }

        $sign = md5('update_rtp' . $requestTime . $secretKey);

        $payload = [
            'rtp' => $rtp,
            'sign' => $sign,
            'requestTime' => $requestTime,
        ];

        Log::info('Buffalo RTP update: prepared payload', [
            'endpoint' => $apiBase . '/ws/rtp',
            'payload' => $payload,
        ]);

        try {
            $response = Http::timeout(10)->post($apiBase . '/ws/rtp', $payload);
        } catch (\Throwable $e) {
            Log::error('Buffalo RTP update: provider request failed', [
                'endpoint' => $apiBase . '/ws/rtp',
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Unable to reach Buffalo provider. Please try again later.');
        }

        $status = $response->status();
        $rawBody = trim($response->body() ?? '');

        Log::info('Buffalo RTP update: provider response received', [
            'endpoint' => $apiBase . '/ws/rtp',
            'status' => $status,
            'body' => $rawBody,
        ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Buffalo provider returned an error.';

            Log::warning('Buffalo RTP update: provider error response', [
                'endpoint' => $apiBase . '/ws/rtp',
                'status' => $status,
                'body' => $rawBody,
            ]);

            return back()
                ->withInput()
                ->with('error', $message);
        }

        $decoded = [];
        if ($rawBody !== '') {
            $decoded = $response->json();

            if (! is_array($decoded)) {
                Log::warning('Buffalo RTP update: invalid provider response', [
                    'endpoint' => $apiBase . '/ws/rtp',
                    'status' => $status,
                    'body' => $rawBody,
                ]);

                return back()
                    ->withInput()
                    ->with('error', 'Invalid response received from Buffalo provider.');
            }
        }

        $savedRtp = isset($decoded['data']['rtp'])
            ? (float) $decoded['data']['rtp']
            : $rtp;

        $setting = BuffaloProviderSetting::getSingleton();
        $setting->update([
            'rtp' => $savedRtp,
            'rtp_request_time' => $requestTime,
        ]);

        Log::info('Buffalo RTP update: stored locally', [
            'stored_rtp' => $setting->rtp,
            'request_time' => $requestTime,
        ]);

        return redirect()
            ->route('admin.buffalo.rtp.index')
            ->with('success', 'RTP updated successfully.');
    }
}


