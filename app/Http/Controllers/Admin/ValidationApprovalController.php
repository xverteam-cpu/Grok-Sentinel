<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ValidationRequest;
use App\Models\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    public function updateValidation(Request $request, ValidationRequest $validationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approved,rejected'],
        ]);

        $validationRequest->status = $validated['action'];
        $validationRequest->reviewed_by = $request->user()->id;
        $validationRequest->reviewed_at = now();
        $validationRequest->save();

        User::query()
            ->whereKey($validationRequest->user_id)
            ->update(['validation_status' => $validated['action']]);

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
