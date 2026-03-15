<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_manage_profile(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ivan Petrov',
            'email' => 'ivan@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('user.email', 'ivan@example.com');

        $token = $registerResponse->json('token');

        $this->getJson('/api/v1/auth/me', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('user.email', 'ivan@example.com');

        $this->getJson('/api/v1/profile', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->postJson('/api/v1/profile', [
            'first_name' => 'Ivan',
            'last_name' => 'Petrov',
            'phone' => '+79991234567',
            'bio' => 'Backend developer',
            'address' => 'Krasnoyarsk',
        ], $this->authHeaders($token))
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Ivan');

        $this->putJson('/api/v1/profile', [
            'bio' => 'Senior backend developer',
        ], $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.bio', 'Senior backend developer');

        $this->getJson('/api/v1/profile', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonPath('data.last_name', 'Petrov');

        $this->getJson('/api/v1/activities', $this->authHeaders($token))
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);

        $this->postJson('/api/v1/auth/logout', [], $this->authHeaders($token))
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        app('auth')->forgetGuards();

        $this->getJson('/api/v1/auth/me', $this->authHeaders($token))
            ->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ivan@example.com',
            'password' => 'secret12345',
        ])->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();

        $tokenOne = $user->createToken('token-one')->plainTextToken;
        $tokenTwo = $user->createToken('token-two')->plainTextToken;

        $this->postJson('/api/v1/auth/logout', [], $this->authHeaders($tokenOne))
            ->assertOk();

        app('auth')->forgetGuards();

        $this->getJson('/api/v1/auth/me', $this->authHeaders($tokenOne))
            ->assertUnauthorized();

        app('auth')->forgetGuards();

        $this->getJson('/api/v1/auth/me', $this->authHeaders($tokenTwo))
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_api_method_not_allowed_returns_json_payload(): void
    {
        $this->post('/api/v1/auth/me')
            ->assertStatus(405)
            ->assertJsonPath('message', 'Method not allowed for this endpoint.')
            ->assertJsonPath('allowed_methods.0', 'GET')
            ->assertJsonPath('allowed_methods.1', 'HEAD');
    }

    public function test_api_not_found_returns_json_payload(): void
    {
        $this->get('/api/v1/unknown-endpoint')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Endpoint not found.');
    }

    public function test_login_endpoint_is_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'ratelimit@example.com',
            'password' => 'secret12345',
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ratelimit@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    private function authHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
        ];
    }
}
