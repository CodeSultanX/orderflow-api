<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $credentials = [
            'email' => 'ermekovsultan@gmail.ru',
            'password' => '123456789',
        ];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/api/login', $credentials);
            $response->assertUnauthorized();
        }
        $response = $this->post('/api/login', $credentials);
        $response->assertTooManyRequests();
    }
}
