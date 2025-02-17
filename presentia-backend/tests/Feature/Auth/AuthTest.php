<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The provided credentials are incorrect',
                 ]);
    }

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

    public function test_google_authentication_new_user()
    {
        $googleUser = (object) [
            'id' => '123456789',
            'name' => 'Adam',
            'email' => 'adam@example.com',
        ];

        // Mock Socialite
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->stateless->user')
            ->andReturn($googleUser);

        $response = $this->getJson('api/auth-google-callback');

        $response->assertRedirect(config('app.frontend_url') . '/login?status=new_user&name=' . urlencode($googleUser->name) . '&email=' . urlencode($googleUser->email) . '&google_id=' . urlencode($googleUser->id));

        $this->assertDatabaseHas('users', [
            'google_id' => $googleUser->id,
            'email' => $googleUser->email,
        ]);
    }

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

    public function test_google_authentication_error()
    {
        // Mock Socialite untuk melempar exception
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Authentication failed.'));

        $response = $this->getJson('api/auth-google-callback');

        $response->assertRedirect(config('app.frontend_url') . '/login?status=error&message=' . urlencode('Authentication failed.'));
    }
}
