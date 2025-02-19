<?php

namespace Tests\Feature;

use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function test_user_can_register()
    {
        $response = $this->postJson('api/register', [
            'fullname' => 'Adam',
            'username' => 'adamUser',
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id' => $this->faker->numerify('##############'),
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'user' => [
                         'id',
                         'fullname',
                         'username',
                         'email',
                         'google_id',
                     ],
                     'token',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => $response->json('user.email'),
        ]);
    }

    #[Test]
    public function test_user_can_register_invalid_fullname()
    {
        $response = $this->postJson('api/register', [
            'fullname' => 'ad',
            'username' => 'adamUser',
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id' => $this->faker->numerify('##############'),
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'errors' => [
                         'fullname',
                     ]
                 ]);
    }

    #[Test]
    public function test_user_can_register_invalid_username()
    {
        $response = $this->postJson('api/register', [
            'fullname' => 'adam',
            'username' => 'ad',
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id' => $this->faker->numerify('##############'),
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'errors' => [
                         'username',
                     ]
                 ]);
    }

    #[Test]
    public function test_user_can_register_invalid_password()
    {
        $response = $this->postJson('api/register', [
            'fullname' => 'adam',
            'username' => 'adam',
            'email' => $this->faker->unique()->safeEmail,
            'password' => '123',
            'password_confirmation' => '123',
            'google_id' => $this->faker->numerify('##############'),
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'errors' => [
                         'password',
                     ]
                 ]);
    }

    #[Test]
    public function test_user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'user' => [
                         'id',
                         'fullname',
                         'username',
                         'email',
                     ],
                     'token',
                 ]);
    }

    #[Test]
    public function test_user_can_login_with_username()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => 'testuser',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'user' => [
                         'id',
                         'fullname',
                         'username',
                         'email',
                     ],
                     'token',
                 ]);
    }

    #[Test]
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(status: 200)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The provided credentials are incorrect',
                 ]);
    }

    #[Test]
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'You are logged out',
                 ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function test_google_authentication_existing_user()
    {
        $googleUser = (object) [
            'id' => '123456789',
            'name' => 'Adam',
            'email' => 'adam@example.com',
        ];

        $user = User::factory()->create([
            'google_id' => $googleUser->id,
            'email' => $googleUser->email,
        ]);

        // Mock Socialite
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->getJson('api/auth-google-callback');

        $response->assertRedirect(config('app.frontend_url') . '/login?status=existing_user&token=' . $token);

        $this->assertDatabaseHas('users', [
            'google_id' => $googleUser->id,
            'email' => $googleUser->email,
        ]);
    }

    #[Test]
    public function test_google_authentication_error()
    {
        // Mock Socialite untuk melempar exception
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Authentication failed.'));

        $response = $this->getJson('api/auth-google-callback');

        $response->assertRedirect(config('app.frontend_url') . '/login?status=error&message=' . urlencode('Authentication failed.'));
    }
}
