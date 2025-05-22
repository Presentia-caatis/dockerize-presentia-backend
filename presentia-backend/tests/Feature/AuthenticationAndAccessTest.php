<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationAndAccessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function user_can_register_login_and_view_empty_dashboard_statistics(): void
    {
        /** ------------------------------------
         *  1) REGISTER
         *  ----------------------------------*/
        $registerPayload = [
            'fullname'              => 'Adam',
            'username'              => 'adamUser',
            'email'                 => 'adamuser@email.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id'             => $this->faker->numerify('##############'),
        ];

        $registerRes = $this->postJson('/api/register', $registerPayload)
                            ->assertStatus(200)
                            ->assertJsonStructure(['user' => ['id', 'email']]);

        $this->assertDatabaseHas('users', ['email' => $registerPayload['email']]);

        /** ------------------------------------
         *  2) LOGIN (email)
         *  ----------------------------------*/
        $loginRes = $this->postJson('/api/login', [
            'email_or_username' => $registerPayload['email'],
            'password'          => $registerPayload['password'],
        ])->assertStatus(200)
          ->assertJsonStructure(['token']);

        $token   = $loginRes->json('token');

        /** ------------------------------------
         *  3) DASHBOARD 
         *  ----------------------------------*/
        $statsRes = $this->withHeader('Authorization', "Bearer {$token}")
                         ->getJson('/api/user/get-by-token');

        $statsRes->assertStatus(200);
    }
}
