<?php

namespace App\Http\Controllers;

use App\Models\ValidationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ValidationRequestController extends Controller
{
    public function storeAppleGiftCard(Request $request): RedirectResponse
    {
        $normalized = [
            'amount' => $request->input('amount'),
            'gift_card_code' => $request->input('gift_card_code', $request->input('giftCardCode', $request->input('code'))),
        ];

        $validated = validator($normalized, [
            'amount' => ['required', 'numeric', 'min:1'],
            'gift_card_code' => ['required', 'string', 'min:4', 'max:255'],
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
