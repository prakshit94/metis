<?php

namespace Tests\Feature;

use App\Modules\Users\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->actingAs(new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]));

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
