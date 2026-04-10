<?php

namespace App\Http\Controllers;

use App\Models\LoginSession;
use App\Models\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $withdrawableBalance = (float) $user->withdrawable_balance;

        if ($withdrawableBalance <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'No withdrawable balance is currently available.',
            ]);
        }

        $countryCode = LoginSession::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->value('country_code');

        $normalized = [
            'bank_name' => trim((string) $request->input('bank_name')),
            'branch_code' => preg_replace('/\D+/', '', (string) $request->input('branch_code')),
            'account_number' => preg_replace('/\D+/', '', (string) $request->input('account_number')),
            'routing_number' => preg_replace('/\D+/', '', (string) $request->input('routing_number')),
            'account_holder' => trim((string) $request->input('account_holder')),
        ];

        $validator = validator($normalized, [
            'bank_name' => ['required', 'string', 'max:255'],
            'branch_code' => ['nullable', 'regex:/^\d{3}$/'],
            'account_number' => ['required', 'regex:/^\d{4,20}$/'],
            'routing_number' => ['nullable', 'regex:/^\d{3,12}$/'],
            'account_holder' => ['required', 'string', 'max:255'],
        ], [
            'branch_code.regex' => 'The branch code must contain exactly 3 digits.',
            'account_number.regex' => 'The account number must contain digits only.',
            'routing_number.regex' => 'The routing number must contain digits only.',
        ]);

        $validator->after(function ($validator) use ($countryCode, $normalized): void {
            if ($countryCode !== 'JP') {
                if (! preg_match('/^\d{3,12}$/', $normalized['routing_number'])) {
                    $validator->errors()->add('routing_number', 'Please enter a valid routing number.');
                }

                return;
            }

            if (! $this->isJapaneseBankName($normalized['bank_name'])) {
                $validator->errors()->add('bank_name', 'Japan-based users must register a Japanese bank.');
            }

            if (! preg_match('/^\d{3}$/', $normalized['branch_code'])) {
                $validator->errors()->add('branch_code', 'Japan-based users must enter a 3-digit branch code.');
            }

            if (! preg_match('/^\d{7}$/', $normalized['account_number'])) {
                $validator->errors()->add('account_number', 'Japan-based users must enter a 7-digit account number.');
            }
        });

        $validated = $validator->validate();

        WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'amount' => $withdrawableBalance,
            'destination' => sprintf(
                '%s | %s',
                $validated['bank_name'],
                $countryCode === 'JP'
                    ? sprintf('Branch: %s | Account: %s', $validated['branch_code'], $validated['account_number'])
                    : sprintf('Account: %s', $validated['account_number']),
            ),
            'reference' => $countryCode === 'JP'
                ? sprintf('Holder: %s | Country: JP', $validated['account_holder'])
                : sprintf('Routing: %s | Holder: %s', $validated['routing_number'], $validated['account_holder']),
            'status' => 'pending',
        ]);

        return back()->with('success', 'Withdrawal request submitted. Awaiting admin review.');
    }

    private function isJapaneseBankName(string $bankName): bool
    {
        $normalizedBankName = mb_strtolower(trim($bankName));
        $knownBanks = [
            'mizuho',
            'mizuhobank',
            'sumitomo mitsui',
            'smbc',
            'mitsubishi ufj',
            'mufg',
            'resona',
            'rakuten bank',
            'paypay bank',
            'japan post bank',
            'ゆうちょ',
            'みずほ',
            '三井住友',
            '三菱ufj',
            'りそな',
            '楽天銀行',
            'paypay銀行',
            '横浜銀行',
            '千葉銀行',
            '福岡銀行',
            '静岡銀行',
            '京都銀行',
            '七十七銀行',
            '八十二銀行',
            '常陽銀行',
            '西日本シティ銀行',
            '信用金庫',
            '信用組合',
            '銀行',
        ];

        foreach ($knownBanks as $knownBank) {
            if (str_contains($normalizedBankName, mb_strtolower($knownBank))) {
                return true;
            }
        }

        return false;
    }
}