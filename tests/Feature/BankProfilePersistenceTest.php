<?php

namespace Tests\Feature;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BankProfilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_user_can_save_bank_profile_securely(): void
    {
        $user = User::factory()->create();

        LoginSession::query()->create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'US',
            'region' => 'California',
            'city' => 'Los Angeles',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
        ]);

        $response = $this->actingAs($user)->post('/bank-profile', [
            'bank_name' => 'Bank of America',
            'account_number' => '12345678',
            'routing_number' => '123456789',
            'account_holder' => 'Kazuki Sawada',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Bank details saved securely to your account.');
        $this->assertDatabaseHas('bank_profiles', [
            'user_id' => $user->id,
            'country_code' => 'US',
        ]);

        $rawProfile = DB::table('bank_profiles')->where('user_id', $user->id)->first();

        $this->assertNotSame('Bank of America', $rawProfile->bank_name);
        $this->assertNotSame('12345678', $rawProfile->account_number);
        $this->assertNotSame('123456789', $rawProfile->routing_number);
        $this->assertNotSame('Kazuki Sawada', $rawProfile->account_holder);
    }

    public function test_japan_based_users_must_save_valid_japanese_bank_profile(): void
    {
        $user = User::factory()->create();

        LoginSession::query()->create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'JP',
            'region' => 'Tokyo',
            'city' => 'Tokyo',
            'user_agent' => 'PHPUnit',
            'metadata' => [],
        ]);

        $response = $this->actingAs($user)->post('/bank-profile', [
            'bank_name' => 'Chase Bank',
            'branch_code' => '12',
            'account_number' => '1234567',
            'account_holder' => '',
        ]);

        $response->assertSessionHasErrors(['bank_name', 'branch_code', 'account_holder']);
        $this->assertDatabaseCount('bank_profiles', 0);
    }
}
