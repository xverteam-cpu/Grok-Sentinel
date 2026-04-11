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

    public function test_login_page_is_reachable_immediately_after_access_is_granted_in_same_session(): void
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

        $this->get(route('login'))
            ->assertOk();
    }

    public function test_http_access_flow_sets_non_secure_device_cookie_for_same_device_reuse(): void
    {
        $token = 'sentinel-link-token';

        config()->set('session.secure', false);

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', 'SENT-ABCD-EFGH'),
            'link_token_hash' => hash('sha256', $token),
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $response = $this
            ->withServerVariables([
                'HTTPS' => 'off',
                'REQUEST_SCHEME' => 'http',
            ])
            ->withHeader('User-Agent', 'PHPUnit Browser')
            ->post('/access', [
                'token' => $token,
            ]);

        $deviceCookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === AccessController::DEVICE_COOKIE);

        $this->assertNotNull($deviceCookie);
        $this->assertFalse($deviceCookie->isSecure());
    }

    public function test_same_device_remains_valid_when_user_agent_changes(): void
    {
        $token = 'sentinel-link-token';
        $deviceId = 'device-one';

        AccessGrant::query()->create([
            'code_hash' => hash('sha256', 'SENT-ABCD-EFGH'),
            'link_token_hash' => hash('sha256', $token),
            'device_id_hash' => hash('sha256', $deviceId),
            'user_agent_hash' => hash('sha256', 'Old Browser'),
            'bound_at' => now(),
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        $response = $this
            ->withHeader('User-Agent', 'Updated Browser')
            ->withCookie(AccessController::DEVICE_COOKIE, $deviceId)
            ->get('/access');

        $response->assertRedirect(route('login'));
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
