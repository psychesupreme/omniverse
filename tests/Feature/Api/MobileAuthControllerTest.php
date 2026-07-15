<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class MobileAuthControllerTest extends TestCase
{
    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Transactionless cleanup
        User::query()->delete();

        // Create a mobile worker
        $this->worker = User::create([
            'name'     => 'Worker One',
            'email'    => 'worker1@acme.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function tearDown(): void
    {
        User::query()->delete();

        parent::tearDown();
    }

    public function test_mobile_worker_can_login_and_receive_sanctum_token()
    {
        // Act: POST login request with valid credentials
        $response = $this->postJson('http://test.localhost/api/v1/mobile/login', [
            'email'    => 'worker1@acme.com',
            'password' => 'password',
        ]);

        // Assert: Verify response structures
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
            ]
        ]);

        $this->assertEquals('worker1@acme.com', $response->json('user.email'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_mobile_worker_cannot_login_with_invalid_credentials()
    {
        // Act: POST login request with invalid password
        $response = $this->postJson('http://test.localhost/api/v1/mobile/login', [
            'email'    => 'worker1@acme.com',
            'password' => 'wrongpassword',
        ]);

        // Assert: 401 Unauthorized
        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials.');
    }
}
