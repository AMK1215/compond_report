<?php

namespace App\Http\Controllers\Agent;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AgentRequest;
use App\Models\Admin\Permission;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\TransferLogRequest;
use App\Models\TransferLog;

class SubAgentController extends Controller
{
    private const AGENT_ROLE = 2;

    /**
     * Display a listing of sub-agents under current agent.
     */
    public function index(): View
    {
        $authUser = auth()->user();

        // Get only direct sub-agents under current agent
        $users = User::with(['roles', 'agent'])
            ->whereHas('roles', fn ($q) => $q->where('role_id', self::AGENT_ROLE))
            ->where('agent_id', $authUser->id) // Only direct children
            ->select('id', 'name', 'user_name', 'phone', 'status', 'referral_code', 'agent_id')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('agent.sub_agent.index', compact('users'));
    }

    /**
     * Show the form for creating a new sub-agent.
     */
    public function create(): View
    {
        $agent_name = $this->generateNextCode('Agent');
        $referral_code = $this->generateReferralCode();

        return view('agent.sub_agent.create', compact('agent_name', 'referral_code'));
    }

    /**
     * Store a newly created sub-agent.
     */
    public function store(AgentRequest $request): RedirectResponse
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

            // Create the sub-agent
            $userPrepare = array_merge(
                $inputs,
                [
                    'password' => Hash::make($inputs['password']),
                    'agent_id' => Auth::id(), // Current agent becomes parent
                    'type' => UserType::Agent->value,
                ]
            );

            $subAgent = User::create($userPrepare);
            $subAgent->roles()->sync(self::AGENT_ROLE);

            // Assign permissions
            $permissions = Permission::whereIn('group', ['agent', 'player_creation', 'deposit_withdraw', 'view_only'])->get();
            $subAgent->permissions()->sync($permissions->pluck('id'));

            // Handle initial transfer if amount provided
            if ($transfer_amount > 0) {
                app(WalletService::class)->transfer(
                    $agent,
                    $subAgent,
                    $transfer_amount,
                    TransactionName::CreditTransfer,
                    [
                        'old_balance' => $subAgent->balanceFloat,
                        'new_balance' => $subAgent->balanceFloat + $transfer_amount,
                    ]
                );

                TransferLog::create([
                    'from_user_id' => $agent->id,
                    'to_user_id' => $subAgent->id,
                    'amount' => $transfer_amount,
                    'type' => 'top_up',
                    'description' => 'Initial deposit to new sub-agent',
                    'meta' => [
                        'transaction_type' => TransactionName::CreditTransfer->value,
                    ],
                ]);
            }

            DB::commit();

            return redirect()->route('agent.sub-agent.index')
                  ->with('successMessage', 'Agent created successfully')
            ->with('password', $request->password)
            ->with('username', $subAgent->user_name)
            ->with('amount', $transfer_amount)
            ->with('link', "https://ag.6tribet.net");;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating sub-agent: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error occurred. Please try again.');
        }
    }

    /**
     * Show the form for editing a sub-agent.
     */
    public function edit(string $id): View
    {
        $user = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($user->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.sub_agent.edit', compact('user'));
    }

    /**
     * Update the specified sub-agent.
     */
    public function update(AgentRequest $request, string $id): RedirectResponse
    {
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $inputs = $request->validated();

        $agent->update([
            'name' => $inputs['name'],
            'phone' => $inputs['phone'],
            'referral_code' => $inputs['referral_code'] ?? $agent->referral_code,
        ]);

        return redirect()->route('agent.sub-agent.index')
            ->with('success', 'Sub-Agent updated successfully!');
    }

    /**
     * Ban/Unban a sub-agent.
     */
    public function banAgent(string $id): RedirectResponse
    {
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $agent->update(['status' => $agent->status == 1 ? 0 : 1]);

        return redirect()->back()->with(
            'success',
            $agent->status == 1 ? 'Sub-Agent activated!' : 'Sub-Agent deactivated!'
        );
    }

    /**
     * Show cash-in form.
     */
    public function getCashIn(string $id): View
    {
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.sub_agent.cash_in', compact('agent'));
    }

    /**
     * Process cash-in to sub-agent.
     */
    public function makeCashIn(string $id): RedirectResponse
    {
        $currentAgent = Auth::user();
        $subAgent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($subAgent->agent_id !== $currentAgent->id) {
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
                $subAgent,
                $amount,
                TransactionName::CreditTransfer
            );

            TransferLog::create([
                'from_user_id' => $currentAgent->id,
                'to_user_id' => $subAgent->id,
                'amount' => $amount,
                'type' => 'top_up',
                'description' => 'Deposit to sub-agent',
            ]);

            DB::commit();

            return redirect()->route('agent.sub-agent.index')
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
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.sub_agent.cash_out', compact('agent'));
    }

    /**
     * Process cash-out from sub-agent.
     */
    public function makeCashOut(string $id): RedirectResponse
    {
        $currentAgent = Auth::user();
        $subAgent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($subAgent->agent_id !== $currentAgent->id) {
            abort(403, 'Unauthorized action.');
        }

        request()->validate(['amount' => 'required|numeric|min:1']);
        $amount = request()->input('amount');

        if ($amount > $subAgent->balanceFloat) {
            return redirect()->back()->with('error', 'Sub-agent has insufficient balance');
        }

        try {
            DB::beginTransaction();

            app(WalletService::class)->transfer(
                $subAgent,
                $currentAgent,
                $amount,
                TransactionName::DebitTransfer
            );

            TransferLog::create([
                'from_user_id' => $subAgent->id,
                'to_user_id' => $currentAgent->id,
                'amount' => $amount,
                'type' => 'withdraw',
                'description' => 'Withdraw from sub-agent',
            ]);

            DB::commit();

            return redirect()->route('agent.sub-agent.index')
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
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('agent.sub_agent.change_password', compact('agent'));
    }

    /**
     * Update password for sub-agent.
     */
    public function makeChangePassword(string $id): RedirectResponse
    {
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        request()->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $agent->update([
            'password' => Hash::make(request()->input('password')),
        ]);

        return redirect()->route('agent.sub-agent.index')
            ->with('success', 'Password changed successfully!');
    }

    /**
     * Show transfer logs for sub-agent.
     */
    public function logs(string $id): View
    {
        $agent = User::findOrFail($id);

        // Verify this sub-agent belongs to current agent
        if ($agent->agent_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $transferLogs = TransferLog::where(function ($query) use ($id) {
            $query->where('from_user_id', $id)
                ->orWhere('to_user_id', $id);
        })
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('agent.sub_agent.logs', compact('agent', 'transferLogs'));
    }

    /**
     * Generate random username.
     */
    private function generateRandomString(): string
    {
        return 'AG'.Str::random(6).rand(1000, 9999);
    }

    /**
     * Generate referral code.
     */
    private function generateReferralCode(): string
    {
        return Str::random(8);
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

