<?php

namespace App\Http\Controllers\Api\Buffalo;

use App\Http\Controllers\Controller;
use App\Enums\TransactionName;
use App\Models\BuffaloProviderSetting;
use App\Models\BuffaloWagerTransaction;
use App\Models\GameReport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\WalletService;
use Bavix\Wallet\Exceptions\InsufficientFunds;
use Illuminate\Support\Facades\Http;

class NewBuffaloCallbackController extends Controller
{
    /**
     * Handle balance inquiry callback from the new Buffalo API.
     */
    public function balance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userId' => ['required', 'string'],
            'sign' => ['nullable', 'string'],
            'requestTime' => ['required'],
        ]);

        $secretKey = config('new_buffalo_key.buffalo_secret_key');

        if (empty($secretKey)) {
            Log::error('New Buffalo balance callback: secret key is not configured');

            return response()->json([
                'data' => null,
                'message' => 'Configuration error',
            ], 500);
        }

        $expectedSignature = md5(
            $validated['requestTime'] .
            $secretKey .
            'balance' .
            $validated['userId']
        );

        if (! hash_equals($expectedSignature, strtolower($validated['sign']))) {
            Log::warning('New Buffalo balance callback: invalid signature', [
                'provided' => $validated['sign'],
                'expected' => $expectedSignature,
                'user_id' => $validated['userId'],
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $userId = $validated['userId'];
        $user = $this->resolveUser($userId);

        if (! $user) {
            return response()->json([
                'data' => null,
                'message' => 'User not found',
            ], 404);
        }

        $balance = $user->wallet?->balanceFloat ?? 0;

        return response()->json([
            'data' => [
                'userId' => (string) $userId,
                'amount' => $balance,
            ],
            'message' => 'Success',
        ]);
    }

    /**
     * Handle updating balance after a spin result.
     */
    public function updateBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'userId' => ['required', 'string'],
            'sign' => ['required', 'string'],
            'requestTime' => ['required'],
            'betAmount' => ['required', 'numeric', 'min:0'],
            'prizeAmount' => ['required', 'numeric', 'min:0'],
            'wagerCode' => ['required', 'string'],
        ]);

        $secretKey = config('new_buffalo_key.buffalo_secret_key');

        if (empty($secretKey)) {
            Log::error('New Buffalo balance update: secret key is not configured');

            return response()->json([
                'data' => null,
                'message' => 'Configuration error',
            ], 500);
        }

        $expectedSignature = md5(
            $validated['requestTime'] .
            $secretKey .
            'update_balance' .
            $validated['userId']
        );

        if (! hash_equals($expectedSignature, strtolower($validated['sign']))) {
            Log::warning('New Buffalo balance update: invalid signature', [
                'provided' => $validated['sign'],
                'expected' => $expectedSignature,
                'user_id' => $validated['userId'],
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $userId = $validated['userId'];
        $user = $this->resolveUser($userId);

        if (! $user) {
            return response()->json([
                'data' => null,
                'message' => 'User not found',
            ], 404);
        }

        $betAmount = (float) $validated['betAmount'];
        $prizeAmount = (float) $validated['prizeAmount'];
        $wagerCode = $validated['wagerCode'];

        if (BuffaloWagerTransaction::where('wager_code', $wagerCode)->exists()) {
            return response()->json([
                'data' => null,
                'message' => 'Duplicate wagerCode',
            ], 400);
        }

        if ($betAmount > $user->balanceFloat) {
            return response()->json([
                'data' => null,
                'message' => 'Insufficient balance!',
            ], 400);
        }

        try {
            DB::transaction(function () use ($user, $betAmount, $prizeAmount, $wagerCode, $validated) {
                $beforeBalance = $user->balanceFloat;
                $agent = $user->agent;
                $agentName = $agent?->user_name;

                if ($betAmount > 0) {
                    app(WalletService::class)->withdraw(
                        $user,
                        $betAmount,
                        TransactionName::GAME_BET,
                        [
                            'wager_code' => $wagerCode,
                            'request_time' => $validated['requestTime'],
                        ]
                    );
                }

                if ($prizeAmount > 0) {
                    app(WalletService::class)->deposit(
                        $user,
                        $prizeAmount,
                        TransactionName::GameWin,
                        [
                            'wager_code' => $wagerCode,
                            'request_time' => $validated['requestTime'],
                        ]
                    );
                }

                $user->refresh();
                $afterBalance = $user->balanceFloat;

                BuffaloWagerTransaction::create([
                    'user_id' => $user->id,
                    'wager_code' => $wagerCode,
                    'bet_amount' => $betAmount,
                    'prize_amount' => $prizeAmount,
                    'net_amount' => $prizeAmount - $betAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                    'player_agent_id' => $agent?->id,
                    'player_agent_name' => $agentName,
                    'request_time' => $validated['requestTime'],
                    'meta' => [
                        'user_identifier' => $validated['userId'],
                    ],
                ]);

                GameReport::create([
                    'user_id' => $user->id,
                    'agent_id' => $agent?->id,
                    'provider_name' => 'Buffalo',
                    'game_type' => 'Buffalo',
                    'wager_code' => $wagerCode,
                    'bet_amount' => $betAmount,
                    'prize_amount' => $prizeAmount,
                    'net_amount' => $prizeAmount - $betAmount,
                    'before_balance' => $beforeBalance,
                    'after_balance' => $afterBalance,
                ]);

                $setting = BuffaloProviderSetting::getSingletonForUpdate();
                $setting->update([
                    'total_bet_amount' => $setting->total_bet_amount + $betAmount,
                    'total_prize_amount' => $setting->total_prize_amount + $prizeAmount,
                    'total_profit' => $setting->total_profit + ($prizeAmount - $betAmount),
                    'spin_count' => $setting->spin_count + 1,
                ]);
            });
        } catch (InsufficientFunds $e) {
            Log::warning('New Buffalo balance update: insufficient funds during processing', [
                'user' => $user->id,
                'wager_code' => $wagerCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Insufficient balance!',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('New Buffalo balance update failed', [
                'error' => $e->getMessage(),
                'wager_code' => $wagerCode,
                'user' => $user->id,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Processing error',
            ], 500);
        }

        $user->refresh();

        return response()->json([
            'data' => [
                'userId' => $validated['userId'],
                'amount' => $user->balanceFloat,
            ],
            'message' => 'Success',
        ]);
    }

    private function resolveUser(string $identifier): ?User
    {
        return is_numeric($identifier)
            ? User::find($identifier)
            : User::where('user_name', $identifier)->first();
    }

    public function launchGame(Request $request): JsonResponse
    {
        $authenticatedPlayer = Auth::user();
        $isAuthenticatedPlayer = $authenticatedPlayer instanceof User;
        $userIdRule = $isAuthenticatedPlayer ? ['nullable', 'string'] : ['required', 'string'];
        $signRule = $isAuthenticatedPlayer ? ['nullable', 'string'] : ['required', 'string'];
        $requestTimeRule = $isAuthenticatedPlayer ? ['nullable'] : ['required'];

        $validated = $request->validate([
            'userId' => $userIdRule,
            'sign' => $signRule,
            'requestTime' => $requestTimeRule,
            'isWeb' => ['nullable', 'boolean'],
        ]);

        $userIdInput = $validated['userId'] ?? null;
        $user = $isAuthenticatedPlayer ? $authenticatedPlayer : $this->resolveUser((string) $userIdInput);
        $userId = (string) ($userIdInput ?? ($user?->user_name ?: $user?->id));

        if ($isAuthenticatedPlayer && $userIdInput && $user) {
            $matchesId = (string) $user->id === (string) $userIdInput;
            $matchesUsername = $user->user_name === $userIdInput;

            if (! $matchesId && ! $matchesUsername) {
                return response()->json([
                    'data' => null,
                    'message' => 'Forbidden: mismatched player identifier',
                ], 403);
            }
        }

        if (! $user) {
            return response()->json([
                'data' => null,
                'message' => 'User not found',
            ], 404);
        }

        $setting = BuffaloProviderSetting::getSingleton();

        if ($setting->is_under_maintenance) {
            return response()->json([
                'data' => null,
                'message' => 'Provider under maintenance',
                'reason' => $setting->maintenance_reason,
            ], 423);
        }

        $apiBase = rtrim(config('new_buffalo_key.buffalo_api_url', ''), '/');
        $secretKey = config('new_buffalo_key.buffalo_secret_key');

        if (empty($apiBase) || empty($secretKey)) {
            Log::error('New Buffalo launch game: configuration missing', [
                'api_base' => $apiBase,
                'secret_key_present' => ! empty($secretKey),
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Configuration error',
            ], 500);
        }

        $requestTime = $validated['requestTime'] ?? (int) round(microtime(true) * 1000);
        $isWeb = $request->boolean('isWeb', true);
        $payload = [
            'userId' => $userId,
            'requestTime' => $requestTime,
            'sign' => md5($requestTime.$secretKey.'launch_game'.$userId),
        ];

        if (! $isAuthenticatedPlayer && ! empty($validated['sign']) && ! hash_equals(strtolower($validated['sign']), $payload['sign'])) {
            Log::warning('New Buffalo launch game: provided sign mismatch', [
                'provided' => $validated['sign'],
                'expected' => $payload['sign'],
                'user_id' => $userId,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Invalid signature',
            ], 400);
        }

        $launchGameEndpoint = $apiBase.'/ws/launch-game?isWeb='.($isWeb ? 'true' : 'false');

        try {
            $response = Http::timeout(10)->post($launchGameEndpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('New Buffalo launch game: provider call failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'api' => $launchGameEndpoint,
                'is_web' => $isWeb,
            ]);

            return response()->json([
                'data' => null,
                'message' => 'Provider unavailable',
            ], 502);
        }

        if (! $response->successful()) {
            Log::warning('New Buffalo launch game: provider error response', [
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

        $providerData = $response->json();

        return response()->json([
            'data' => $providerData,
            'message' => 'Success',
        ]);
    }
}

