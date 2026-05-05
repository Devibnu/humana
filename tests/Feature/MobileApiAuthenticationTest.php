<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_overtime_requires_authentication_with_json_response(): void
    {
        $this->getJson('/api/mobile/overtimes')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_mobile_leave_requires_authentication_with_json_response(): void
    {
        $this->getJson('/api/mobile/leaves')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}