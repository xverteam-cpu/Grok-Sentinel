<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('session.driver', 'array');
        config()->set('app.maintenance.driver', 'file');
        config()->set('app.maintenance.store', 'array');
    }

    public function test_user_can_store_a_manual_locale_preference(): void
    {
        $response = $this
            ->from('/access')
            ->post(route('locale.update'), [
                'locale' => 'ja',
            ]);

        $response->assertRedirect('/access');
        $response->assertSessionHas('preferred_locale', 'ja');
        $response->assertSessionHas('locale', 'ja');
    }

    public function test_manual_locale_preference_overrides_automatic_locale_selection(): void
    {
        $this->withSession([
            'preferred_locale' => 'ja',
        ])->get('/access');

        $this->assertSame('ja', app()->getLocale());
    }
}