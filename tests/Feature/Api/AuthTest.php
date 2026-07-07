<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthTest extends TestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure users and personal access tokens tables are clean for each test
        User::query()->delete();
        DB::table('personal_access_tokens')->delete();
    }

    /**
     * Test user login with valid credentials.
     */
    public function test_it_authenticates_user_and_returns_token(): void
    {
        $user = User::create([
            'name' => 'Test Mobile User',
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('http://test.localhost/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user_id',
                'name',
            ]);

        $this->assertEquals($user->id, $response->json('user_id'));
        $this->assertEquals($user->name, $response->json('name'));
        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test user login fails with invalid credentials.
     */
    public function test_it_rejects_invalid_login_credentials(): void
    {
        User::create([
            'name' => 'Test Mobile User',
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('http://test.localhost/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }

    /**
     * Test sync routes block unauthenticated access.
     */
    public function test_it_blocks_unauthenticated_access_to_sync_routes(): void
    {
        $response = $this->postJson('http://test.localhost/api/v1/sync/pull', [
            'collections' => ['outlets'],
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user logout revokes access token.
     */
    public function test_it_revokes_token_on_logout(): void
    {
        $user = User::create([
            'name' => 'Test Mobile User',
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $token = $user->createToken('mobile-sync-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('http://test.localhost/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Token revoked successfully.',
            ]);

        $this->assertEquals(0, DB::table('personal_access_tokens')->count());
    }
}
