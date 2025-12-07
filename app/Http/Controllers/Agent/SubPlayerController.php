<?php

namespace App\Http\Controllers\Agent;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlayerRequest;
use App\Models\TransferLog;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubPlayerController extends Controller
{
    private const PLAYER_ROLE = 3;

    /**
     * Display a listing of players under current agent.
     */
    public function index(): View
    {
        $authUser = auth()->user();

        // Get only direct players under current agent
        $users = User::with(['roles', 'agent'])
            ->whereHas('roles', fn ($q) => $q->where('role_id', self::PLAYER_ROLE))
            ->where('agent_id', $authUser->id) // Only direct children
            ->select('id', 'name', 'user_name', 'phone', 'status', 'agent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('agent.player.index', compact('users'));
    }

    /**
     * Show the form for creating a new player.
     */
    public function create(): View
    {
        $player_name = $this->generateNextCode('Player');

        return view('agent.player.create', compact('player_name'));
    }

    /**
     * Store a newly created player.
     */
    public function store(PlayerRequest $request): RedirectResponse
    {
        $agent = Auth::user();
        $inputs = $request->validated();

        // Check if agent has sufficient balance
        if (isset($inputs['amount']) && $inputs['amount'] > $agent->balanceFloat) {
            return redirect()->back()->with('error', 'Insufficient Balance');
        }

        $transfer_amount = $inputs['amount'] ?? 0;

        try {
            DB::beginTransaction();

            // Create the player
            $userPrepare = array_merge(
                $inputs,
                [
                    'password' => Hash::make($inputs['password']),
                    'agent_id' => Auth::id(), // Current agent becomes parent
                    'type' => UserType::Player->value,
                ]
            );

            $player = User::create($userPrepare);
            $player->roles()->sync(self::PLAYER_ROLE);

            // Handle initial transfer if amount provided
            if ($transfer_amount > 0) {
                app(WalletService::class)->transfer(
                    $agent,
                    $player,
                    $transfer_amount,
                    TransactionName::CreditTransfer,
                    [
                        'old_balance' => $player->balanceFloat,
                        'new_balance' => $player->balanceFloat + $transfer_amount,
                    ]
                );

                TransferLog::create([
                    'from_user_id' => $agent->id,
                    'to_user_id' => $player->id,
                    'amount' => $transfer_amount,
                    'type' => 'top_up',
                    'description' => 'Initial deposit to new player',
                    'meta' => [
                        'transaction_type' => TransactionName::CreditTransfer->value,
                    ],
                ]);
            }

            DB::commit();

                return redirect()->route('agent.player.index')
                ->with('successMessage', 'Player created successfully')
                ->with('amount', $inputs['amount']??0)
                ->with('password', $request->password)
                ->with('link', 'https://m.6tribet.net')
                ->with('appLink','https://ag.6tribet.net/assets/6tribet.apk')
                ->with('username', $player->user_name);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating player: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error occurred. Please try again.');
        }
    }

    /**
     * Show the form for editing a player.
     */
    public function edit(string $id): View
    {
        $user = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($user->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.player.edit', compact('user'));
    }

    /**
     * Update the specified player.
     */
    public function update(PlayerRequest $request, string $id): RedirectResponse
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $inputs = $request->validated();

        $player->update([
            'name' => $inputs['name'],
            'phone' => $inputs['phone'],
        ]);

        return redirect()->route('agent.player.index')
            ->with('success', 'Player updated successfully!');
    }

    /**
     * Ban/Unban a player.
     */
    public function banPlayer(string $id): RedirectResponse
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $player->update(['status' => $player->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            $player->status == 1 ? 'Player activated!' : 'Player deactivated!'
        );
    }

    /**
     * Show cash-in form.
     */
    public function getCashIn(string $id): View
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.player.cash_in', compact('player'));
    }

    /**
     * Process cash-in to player.
     */
    public function makeCashIn(string $id): RedirectResponse
    {
        $currentAgent = Auth::user();
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== $currentAgent->id) {
            abort(403, 'Unauthorized action.');
        }

        request()->validate(['amount' => 'required|numeric|min:1']);
        $amount = request()->input('amount');

        if ($amount > $currentAgent->balanceFloat) {
            return redirect()->back()->with('error', 'Insufficient Balance');
        }

        try {
            DB::beginTransaction();

            app(WalletService::class)->transfer(
                $currentAgent,
                $player,
                $amount,
                TransactionName::CreditTransfer
            );

            TransferLog::create([
                'from_user_id' => $currentAgent->id,
                'to_user_id' => $player->id,
                'amount' => $amount,
                'type' => 'top_up',
                'description' => 'Deposit to player',
            ]);

            DB::commit();

            return redirect()->route('agent.player.index')
                ->with('success', 'Deposit successful!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cash-in error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Transaction failed.');
        }
    }

    /**
     * Show cash-out form.
     */
    public function getCashOut(string $id): View
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.player.cash_out', compact('player'));
    }

    /**
     * Process cash-out from player.
     */
    public function makeCashOut(string $id): RedirectResponse
    {
        $currentAgent = Auth::user();
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== $currentAgent->id) {
            abort(403, 'Unauthorized action.');
        }

        request()->validate(['amount' => 'required|numeric|min:1']);
        $amount = request()->input('amount');

        if ($amount > $player->balanceFloat) {
            return redirect()->back()->with('error', 'Player has insufficient balance');
        }

        try {
            DB::beginTransaction();

            app(WalletService::class)->transfer(
                $player,
                $currentAgent,
                $amount,
                TransactionName::DebitTransfer
            );

            TransferLog::create([
                'from_user_id' => $player->id,
                'to_user_id' => $currentAgent->id,
                'amount' => $amount,
                'type' => 'withdraw',
                'description' => 'Withdraw from player',
            ]);

            DB::commit();

            return redirect()->route('agent.player.index')
                ->with('success', 'Withdrawal successful!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cash-out error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Transaction failed.');
        }
    }

    /**
     * Show change password form.
     */
    public function getChangePassword(string $id): View
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.player.change_password', compact('player'));
    }

    /**
     * Update password for player.
     */
    public function makeChangePassword(string $id): RedirectResponse
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        request()->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $player->update([
            'password' => Hash::make(request()->input('password')),
        ]);

        return redirect()->route('agent.player.index')
            ->with('success', 'Password changed successfully!');
    }

    /**
     * Show transfer logs for player.
     */
    public function logs(string $id): View
    {
        $player = User::findOrFail($id);

        // Verify this player belongs to current agent
        if ($player->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transferLogs = TransferLog::where(function ($query) use ($id) {
            $query->where('from_user_id', $id)
                ->orWhere('to_user_id', $id);
        })
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('agent.player.logs', compact('player', 'transferLogs'));
    }

    /**
     * Generate random username.
     */
    private function generateRandomString(): string
    {
        return 'PL'.Str::random(6).rand(1000, 9999);
    }


    private function generateNextCode($role) {
            $prefix = $role === 'Agent' ? 'SA' : 'SP';

        $lastUser = User::where('user_name', 'like', $prefix.'%')
                        ->orderBy('id', 'desc')
                        ->first();

        if ($lastUser && isset($lastUser->user_name)) {
            $lastNumber = (int) substr($lastUser->user_name, 2);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $nextCode = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return $nextCode;
    }

}

