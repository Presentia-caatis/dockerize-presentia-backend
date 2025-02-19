<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_update_their_profile()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);
        Sanctum::actingAs($user);

        $updatedData = [
            'fullname' => 'Updated Name',
            'username' => 'updated_username',
            'email' => 'updatedemail@example.com',
            'password' => 'password123'
        ];

        $response = $this->putJson("/api/user/{$user->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'User updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'fullname' => 'Updated Name',
            'username' => 'updated_username',
            'email' => 'updatedemail@example.com',
        ]);
    }

    #[Test]
    public function user_can_change_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/user/change-password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Password changed successfully',
                 ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
