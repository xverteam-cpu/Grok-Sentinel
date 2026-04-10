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
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ValidationApprovalController extends Controller
{
    public function index(): Response
    {
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
            'pendingValidations' => $pendingValidations,
            'pendingWithdrawals' => $pendingWithdrawals,
            'activeAccessGrantsCount' => AccessGrant::query()->active()->count(),
        ]);
    }

    public function generateAccessGrant(Request $request): RedirectResponse
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
            'created_by' => $request->user()->id,
        ]);

        return back()->with([
            'success' => 'Private access credential generated.',
            'generatedAccess' => [
                'code' => $code,
                'link' => route('access.link', ['token' => $token]),
            ],
        ]);
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

        $withdrawalRequest->status = $validated['action'];
        $withdrawalRequest->reviewed_by = $request->user()->id;
        $withdrawalRequest->reviewed_at = now();
        $withdrawalRequest->save();

        return back()->with('success', 'Withdrawal request updated successfully.');
    }
}
