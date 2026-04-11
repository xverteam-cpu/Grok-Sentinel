<?php

namespace Tests\Feature;

use App\Models\AccessGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessGrantGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_admin_can_generate_access_grant_for_existing_user_from_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'Access Target',
            'email' => 'target@example.com',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from('/admin/dashboard')
            ->post(route('admin.access-grants.users.store', $user), []);

        $response->assertRedirect('/admin/dashboard');
        $response->assertSessionHas('success', 'Private access credential generated for Access Target.');
        $response->assertSessionHas('generatedAccess', function (array $generatedAccess) use ($user): bool {
            return $generatedAccess['name'] === $user->name
                && $generatedAccess['email'] === $user->email
                && $generatedAccess['password'] === null
                && isset($generatedAccess['code'], $generatedAccess['link_token'], $generatedAccess['link']);
        });

        $generatedAccess = session('generatedAccess');

        $this->assertDatabaseHas('access_grants', [
            'code_hash' => hash('sha256', $generatedAccess['code']),
            'link_token_hash' => hash('sha256', $generatedAccess['link_token']),
            'created_by' => $admin->id,
        ]);
        $this->assertSame(1, AccessGrant::query()->count());
    }
}