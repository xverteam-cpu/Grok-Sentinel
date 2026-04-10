<?php

namespace Tests\Feature;

use App\Http\Controllers\AccessController;
use App\Models\AccessGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessGrantFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_access_link_get_only_prefills_confirmation_and_does_not_bind_device(): void
    {
        $token = 'sentinel-link-token';

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', 'SENT-ABCD-EFGH'),
            'link_token_hash' => hash('sha256', $token),
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $response = $this->get("/access/{$token}");

        $response->assertRedirect('/access?token=sentinel-link-token&link=1');
        $this->assertDatabaseHas('access_grants', [
            'link_token_hash' => hash('sha256', $token),
            'device_id_hash' => null,
            'user_agent_hash' => null,
            'bound_at' => null,
        ]);
        $this->assertFalse(session()->has('private_access_granted'));
    }

    public function test_access_link_requires_explicit_post_to_bind_device(): void
    {
        $token = 'sentinel-link-token';

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', 'SENT-ABCD-EFGH'),
            'link_token_hash' => hash('sha256', $token),
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $response = $this
            ->withHeader('User-Agent', 'PHPUnit Browser')
            ->post('/access', [
                'token' => $token,
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Device access granted.');

        $grant = AccessGrant::query()->where('link_token_hash', hash('sha256', $token))->firstOrFail();

        $this->assertNotNull($grant->device_id_hash);
        $this->assertNotNull($grant->user_agent_hash);
        $this->assertNotNull($grant->bound_at);
        $this->assertTrue(session()->has('private_access_granted'));
    }

    public function test_bound_access_link_cannot_be_claimed_by_another_device(): void
    {
        $token = 'sentinel-link-token';
        $deviceOne = 'device-one';

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', 'SENT-ABCD-EFGH'),
            'link_token_hash' => hash('sha256', $token),
            'device_id_hash' => hash('sha256', $deviceOne),
            'user_agent_hash' => hash('sha256', 'Trusted Browser'),
            'bound_at' => now(),
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $response = $this
            ->from('/access')
            ->withHeader('User-Agent', 'Different Browser')
            ->withCookie(AccessController::DEVICE_COOKIE, 'device-two')
            ->post('/access', [
                'token' => $token,
            ]);

        $response->assertRedirect('/access');
        $response->assertSessionHasErrors('code');
    }
}
