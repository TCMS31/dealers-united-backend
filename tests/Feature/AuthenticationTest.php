<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Fortify handles credentials; the app replaces its responses with JSON that
 * carries a Sanctum bearer token. These tests pin that contract, because the
 * client has nothing else to authenticate with.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'correct-horse-battery-staple';

    #[Test]
    public function registration_returns_the_user_and_a_usable_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ]);

        $response->assertOk()->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->withToken($response->json('token'))
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('email', 'ada@example.com');
    }

    #[Test]
    public function registration_requires_a_matching_password_confirmation(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => 'something-else',
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('password');
    }

    #[Test]
    public function login_returns_a_token(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'ada@example.com',
            'password' => self::PASSWORD,
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }

    #[Test]
    public function login_with_the_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
            'password' => self::PASSWORD,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'ada@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * The user payload must never carry the password hash or the two-factor
     * columns the users table still has from the Fortify migration.
     */
    /**
     * Fortify's guest middleware used to redirect an already-signed-in caller
     * to "/home", a route this application never defines.
     */
    #[Test]
    public function logging_in_while_already_signed_in_answers_in_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertStatus(Response::HTTP_CONFLICT)
            ->assertJsonStructure(['message']);
    }

    /**
     * Logging out used to call ->currentAccessToken() on a null user (Fortify
     * logs the guard out first) and then redirect to an undefined property.
     */
    #[Test]
    public function logout_answers_the_api_instead_of_crashing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/logout')->assertNoContent();
    }

    #[Test]
    public function the_user_payload_never_leaks_credentials(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token')
            ->assertJsonMissingPath('two_factor_secret')
            ->assertJsonMissingPath('two_factor_recovery_codes');
    }
}
