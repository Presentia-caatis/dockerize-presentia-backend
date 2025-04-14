<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait TestCaseHelpers
{
    use RefreshDatabase;

    protected $token;
    protected $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create([
            'password' => bcrypt('123') 
        ]);

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->authUser->email,
            'password' => '123',  
        ]);

        $this->token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }
}