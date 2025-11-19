<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an authenticated admin user
        $this->seed(PermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('Admin');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
        'success',
        'code',
        'message',
        'data' => [
            'original' => [
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ]
        ]
    ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {

        $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
{
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    // Login and get token
    $login = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $token = $login->json('data.original.access_token');

    // Logout request
    $response = $this->postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer ' . $token,
    ]);

    $response->assertStatus(200);

    $response->assertJson([
        'success' => true,
        'message' => 'Successfully logged out!',
    ]);
}

}
