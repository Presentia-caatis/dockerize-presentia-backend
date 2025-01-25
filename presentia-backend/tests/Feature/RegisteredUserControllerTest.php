<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;


class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_registration_successful()
    {
        $response = $this->postJson('/register', [
            'username' => 'TestUser',
            'email' => 'newuser@example.com',
            'fullname' => 'New User',
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }
}