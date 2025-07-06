<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AuthenticationAndAccountManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private function create_user(): array
    {
        $payload = [
            'fullname'              => 'Adam',
            'username'              => 'adamUser',
            'email'                 => 'adamuser@email.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id'             => $this->faker->numerify('##############'),
        ];

        $this->postJson('/api/register', $payload)
                            ->assertStatus(200)
                            ->assertJsonStructure(['user' => ['id', 'email']]);

        return $payload;
    }

    #[Test]
    public function user_can_login_update_profil_and_logout(): void
    {
        $registerPayload = $this->create_user();

        // --- 1. Login ---
        $loginRes = $this->postJson('/api/login', [
            'email_or_username' => $registerPayload['email'],
            'password'          => $registerPayload['password'],
        ])->assertStatus(200)
          ->assertJsonStructure(['token']);

        $token = $loginRes->json('token');
        $user  = User::where('email', $registerPayload['email'])->first();

        // --- 2. Update Profile ---
        $updatePayload = [
            'fullname' => 'Updated Name',
            'username' => 'updatedusername',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/user/", $updatePayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'User updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'fullname' => 'Updated Name',
            'username' => 'updatedusername',
        ]);

        // --- 3. Logout ---
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'You are logged out',
                 ]);
    }
}
