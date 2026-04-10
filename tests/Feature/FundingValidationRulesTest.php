<?php

namespace Tests\Feature;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundingValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_apple_gift_card_validation_requires_exactly_sixteen_digits(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/validate-funds/apple-gift-card', [
            'amount' => 1065000,
            'gift_card_code' => '123456789012345',
        ]);

        $response->assertSessionHasErrors('gift_card_code');
        $this->assertDatabaseCount('validation_requests', 0);
    }

    public function test_japan_based_users_must_submit_japanese_bank_details(): void
    {
        $user = User::factory()->create([
            'withdrawable_balance' => 1065000,
        ]);

        LoginSession::query()->create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'JP',
            'region' => 'Tokyo',
            'city' => 'Tokyo',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
        ]);

        $response = $this->actingAs($user)->post('/withdrawals', [
            'bank_name' => 'Chase Bank',
            'account_number' => '1234567',
            'routing_number' => '123456',
        ]);

        $response->assertSessionHasErrors(['bank_name', 'account_number', 'routing_number']);
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    public function test_japan_based_users_can_submit_valid_japanese_bank_details(): void
    {
        $user = User::factory()->create([
            'withdrawable_balance' => 1065000,
        ]);

        LoginSession::query()->create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'JP',
            'region' => 'Kanagawa',
            'city' => 'Yokohama',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
        ]);

        $response = $this->actingAs($user)->post('/withdrawals', [
            'bank_name' => '横浜銀行',
            'account_number' => '0138',
            'routing_number' => '123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Withdrawal request submitted. Awaiting admin review.');
        $this->assertDatabaseHas('withdrawal_requests', [
            'user_id' => $user->id,
            'amount' => 1065000,
            'destination' => '横浜銀行 | Bank No.: 0138',
            'reference' => 'Routing: 123 | Country: JP',
            'status' => 'pending',
        ]);
    }
}