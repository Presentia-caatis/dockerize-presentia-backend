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

        // Buat user dan login
        $user = User::factory()->create();
        $response = $this->postJson('api/login', [
            'email_or_username' => $user->email,
            'password' => '123',  
        ]);

        // Simpan token 
        $this->token = $response->json('token');

        // Menyimpan token di header
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }
}