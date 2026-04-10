<?php

namespace App\Http\Controllers;

use App\Models\ValidationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValidationRequestController extends Controller
{
    public function storeAppleGiftCard(Request $request): RedirectResponse
    {
        $giftCardCode = preg_replace('/\D+/', '', (string) $request->input('gift_card_code', $request->input('giftCardCode', $request->input('code'))));

        $normalized = [
            'amount' => $request->input('amount'),
            'gift_card_code' => $giftCardCode,
        ];

        $validated = validator($normalized, [
            'amount' => ['required', 'numeric', 'min:1'],
            'gift_card_code' => ['required', 'regex:/^\d{16}$/'],
        ], [
            'gift_card_code.regex' => 'Apple gift card validation requires exactly 16 digits.',
        ])->validate();

        ValidationRequest::create([
            'user_id' => $request->user()->id,
            'method' => 'apple_gift_card',
            'amount' => $validated['amount'],
            'gift_card_code' => $validated['gift_card_code'],
            'status' => 'pending',
        ]);

        $request->user()->forceFill([
            'validation_status' => 'pending',
        ])->save();

        return back()->with('success', 'Validation submitted. Awaiting admin review.');
    }
}
