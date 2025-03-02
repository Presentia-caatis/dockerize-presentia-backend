<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait TestCaseHelpers
{
    use RefreshDatabase;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $response = $this->postJson('api/login', [
            'email_or_username' => $user->email,
            'password' => '123',  
        ]);

        $this->token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }
}