<?php

namespace Tests\Feature;

use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationAccessUnitTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function test_user_can_access_landing_page()
    {
        $response = $this->getJson('/');

        $response->assertStatus(200);
    }

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
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(status: 401)
                 ->assertJson([
                     'status' => 'failed',
                     'message' => 'The provided credentials are incorrect',
                 ]);
    }

    #[Test]
    public function test_google_authentication()
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

        $response = $this->getJson('api/auth-google-callback');

        $redirectUrl = $response->headers->get('Location');
    
        // Cek URL redirect ke frontend login dengan parameter yang benar
        $this->assertStringStartsWith(config('app.frontend_url') . '/login?status=existing_user&token=', $redirectUrl);
    
        // Parse parameter URL
        parse_str(parse_url($redirectUrl, PHP_URL_QUERY), $queryParams);
    
        $this->assertEquals('existing_user', $queryParams['status']);
        $this->assertArrayHasKey('token', $queryParams);
        $this->assertNotEmpty($queryParams['token']);
    
        // Validasi token milik user 
        $tokenParts = explode('|', $queryParams['token']);
        $tokenHash = hash('sha256', $tokenParts[1] ?? '');
    
        $this->assertTrue(
            $user->tokens()->where('token', $tokenHash)->exists(),
            'The token returned does not belong to the expected user.'
        );

        $this->assertDatabaseHas('users', [
            'google_id' => $googleUser->id,
            'email' => $googleUser->email,
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

}
