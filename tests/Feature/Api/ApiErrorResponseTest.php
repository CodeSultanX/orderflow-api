<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiErrorResponseTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_unknown_api_route_returns_consistent_json_error(): void
    {
        $response = $this->getJson('/api/unknown-route');

        $response->assertNotFound();
        $response->assertExactJson(['message' => 'Resource not found']);
    }
}
