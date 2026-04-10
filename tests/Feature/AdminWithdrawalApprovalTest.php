<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWithdrawalApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_admin_approval_reduces_user_withdrawable_balance(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'withdrawable_balance' => 1065000,
        ]);

        $withdrawalRequest = WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'amount' => 250000,
            'destination' => '横浜銀行 | Branch: 123 | Account: 0123456',
            'reference' => 'Holder: サワダ カズキ | Country: JP',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch("/admin/withdrawals/{$withdrawalRequest->id}", [
            'action' => 'approved',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Withdrawal request updated successfully.');
        $this->assertSame('815000.00', $user->fresh()->withdrawable_balance);
        $this->assertDatabaseHas('withdrawal_requests', [
            'id' => $withdrawalRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_cannot_approve_withdrawal_when_balance_is_no_longer_available(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'withdrawable_balance' => 100000,
        ]);

        $withdrawalRequest = WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'amount' => 250000,
            'destination' => 'Bank of America | Account: 12345678',
            'reference' => 'Routing: 123456789 | Holder: Kazuki Sawada',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->from('/admin/dashboard')->patch("/admin/withdrawals/{$withdrawalRequest->id}", [
            'action' => 'approved',
        ]);

        $response->assertSessionHasErrors('amount');
        $response->assertRedirect('/admin/dashboard');
        $this->assertSame('100000.00', $user->fresh()->withdrawable_balance);
        $this->assertSame('pending', $withdrawalRequest->fresh()->status);
    }

    public function test_reviewed_withdrawal_cannot_be_processed_twice(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'withdrawable_balance' => 1065000,
        ]);

        $withdrawalRequest = WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'amount' => 250000,
            'destination' => '横浜銀行 | Branch: 123 | Account: 0123456',
            'reference' => 'Holder: サワダ カズキ | Country: JP',
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->from('/admin/dashboard')->patch("/admin/withdrawals/{$withdrawalRequest->id}", [
            'action' => 'rejected',
        ]);

        $response->assertSessionHasErrors('action');
        $response->assertRedirect('/admin/dashboard');
        $this->assertSame('approved', $withdrawalRequest->fresh()->status);
        $this->assertSame('1065000.00', $user->fresh()->withdrawable_balance);
    }
}
