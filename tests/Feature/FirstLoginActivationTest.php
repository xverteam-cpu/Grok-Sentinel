<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstLoginActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_on_first_login_are_redirected_to_activation_screen(): void
    {
        $user = User::factory()->create([
            'is_first_login' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/first-login');
    }

    public function test_activation_marks_user_as_activated(): void
    {
        $user = User::factory()->create([
            'is_first_login' => true,
        ]);

        $response = $this->actingAs($user)->post('/activate');

        $response->assertOk();
        $this->assertFalse($user->fresh()->is_first_login);
    }
}