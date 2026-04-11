<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessGrant;
use App\Models\User;
use App\Models\ValidationRequest;
use App\Models\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ValidationApprovalController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'email',
                'is_admin',
                'is_first_login',
                'validation_status',
                'withdrawable_balance',
                'email_verified_at',
                'created_at',
            ]);

        $pendingValidations = ValidationRequest::query()
            ->with('user:id,name,email')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get([
                'id',
                'user_id',
                'method',
                'amount',
                'gift_card_code',
                'status',
                'created_at',
            ]);

        $pendingWithdrawals = WithdrawalRequest::query()
            ->with('user:id,name,email')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get([
                'id',
                'user_id',
                'amount',
                'destination',
                'reference',
                'status',
                'created_at',
            ]);

        return Inertia::render('Admin/ValidationApproval', [
            'users' => $users,
            'pendingValidations' => $pendingValidations,
            'pendingWithdrawals' => $pendingWithdrawals,
            'activeAccessGrantsCount' => AccessGrant::query()->active()->count(),
        ]);
    }

    public function generateAccessGrant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'withdrawable_balance' => ['required', 'numeric', 'min:0'],
        ]);

        $targetUser = User::query()->updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'password' => $validated['password'],
                'email_verified_at' => now(),
                'is_admin' => false,
                'is_first_login' => true,
                'validation_status' => 'approved',
                'withdrawable_balance' => $validated['withdrawable_balance'],
            ],
        );

        return $this->redirectWithGeneratedAccess(
            $request,
            $targetUser,
            'Private access credential generated.',
            $validated['password'],
        );
    }

    public function generateUserAccessGrant(Request $request, User $user): RedirectResponse
    {
        return $this->redirectWithGeneratedAccess(
            $request,
            $user,
            sprintf('Private access credential generated for %s.', $user->name),
        );
    }

    private function redirectWithGeneratedAccess(
        Request $request,
        User $targetUser,
        string $successMessage,
        ?string $password = null,
    ): RedirectResponse {
        [$code, $token] = $this->createAccessGrant($request->user()->id);

        return back()->with([
            'success' => $successMessage,
            'generatedAccess' => $this->buildGeneratedAccessPayload($targetUser, $code, $token, $password),
        ]);
    }

    private function createAccessGrant(int $createdBy): array
    {
        $code = sprintf(
            'SENT-%s-%s',
            Str::upper(Str::random(4)),
            Str::upper(Str::random(4)),
        );
        $token = Str::random(48);

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', $code),
            'link_token_hash' => hash('sha256', $token),
            'created_by' => $createdBy,
        ]);

        return [$code, $token];
    }

    private function buildGeneratedAccessPayload(User $targetUser, string $code, string $token, ?string $password = null): array
    {
        return [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'password' => $password,
            'withdrawable_balance' => $targetUser->withdrawable_balance,
            'code' => $code,
            'link_token' => $token,
            'link' => route('access.link', ['token' => $token]),
        ];
    }

    public function resetRegisteredDevices(): RedirectResponse
    {
        AccessGrant::query()->update([
            'device_id_hash' => null,
            'user_agent_hash' => null,
            'bound_at' => null,
            'last_used_at' => null,
            'revoked_at' => null,
        ]);

        return back()->with('success', 'All registered devices have been reset. Existing access credentials can now be redeemed again on a fresh device.');
    }

    public function updateValidation(Request $request, ValidationRequest $validationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
        ]);

        DB::transaction(function () use ($request, $validationRequest, $validated): void {
            $previousStatus = $validationRequest->status;

            $validationRequest->status = $validated['action'];
            $validationRequest->reviewed_by = $request->user()->id;
            $validationRequest->reviewed_at = now();
            $validationRequest->save();

            $user = User::query()->lockForUpdate()->findOrFail($validationRequest->user_id);
            $user->validation_status = $validated['action'];

            if ($validated['action'] === 'approved' && $previousStatus !== 'approved') {
                $user->withdrawable_balance = round(
                    (float) $user->withdrawable_balance + (float) $validationRequest->amount,
                    2,
                );
            }

            $user->save();
        });

        return back()->with('success', 'Validation request updated successfully.');
    }

    public function updateWithdrawal(Request $request, WithdrawalRequest $withdrawalRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
        ]);

        DB::transaction(function () use ($request, $withdrawalRequest, $validated): void {
            $lockedWithdrawal = WithdrawalRequest::query()
                ->lockForUpdate()
                ->findOrFail($withdrawalRequest->id);

            if ($lockedWithdrawal->status !== 'pending') {
                throw ValidationException::withMessages([
                    'action' => 'This withdrawal request has already been reviewed.',
                ]);
            }

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($lockedWithdrawal->user_id);

            if ($validated['action'] === 'approved' && (float) $user->withdrawable_balance < (float) $lockedWithdrawal->amount) {
                throw ValidationException::withMessages([
                    'amount' => 'The user no longer has enough withdrawable balance for this request.',
                ]);
            }

            $lockedWithdrawal->status = $validated['action'];
            $lockedWithdrawal->reviewed_by = $request->user()->id;
            $lockedWithdrawal->reviewed_at = now();
            $lockedWithdrawal->save();

            if ($validated['action'] === 'approved') {
                $user->withdrawable_balance = round(
                    (float) $user->withdrawable_balance - (float) $lockedWithdrawal->amount,
                    2,
                );
                $user->save();
            }
        });

        return back()->with('success', 'Withdrawal request updated successfully.');
    }
}
