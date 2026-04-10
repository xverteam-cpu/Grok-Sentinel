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
            'account_number' => preg_replace('/\D+/', '', (string) $request->input('account_number')),
            'routing_number' => preg_replace('/\D+/', '', (string) $request->input('routing_number')),
        ];

        $validator = validator($normalized, [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'regex:/^\d{4,20}$/'],
            'routing_number' => ['required', 'regex:/^\d{3,12}$/'],
        ], [
            'account_number.regex' => 'The bank number must contain digits only.',
            'routing_number.regex' => 'The routing number must contain digits only.',
        ]);

        $validator->after(function ($validator) use ($countryCode, $normalized): void {
            if ($countryCode !== 'JP') {
                return;
            }

            if (! $this->isJapaneseBankName($normalized['bank_name'])) {
                $validator->errors()->add('bank_name', 'Japan-based users must register a Japanese bank.');
            }

            if (! preg_match('/^\d{4}$/', $normalized['account_number'])) {
                $validator->errors()->add('account_number', 'Japan-based users must enter a 4-digit Japanese bank number.');
            }

            if (! preg_match('/^\d{3}$/', $normalized['routing_number'])) {
                $validator->errors()->add('routing_number', 'Japan-based users must enter a 3-digit Japanese routing number.');
            }
        });

        $validated = $validator->validate();

        WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'amount' => $withdrawableBalance,
            'destination' => sprintf(
                '%s | %s: %s',
                $validated['bank_name'],
                $countryCode === 'JP' ? 'Bank No.' : 'Account',
                $validated['account_number'],
            ),
            'reference' => sprintf('Routing: %s%s', $validated['routing_number'], $countryCode === 'JP' ? ' | Country: JP' : ''),
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