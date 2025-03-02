<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use PHPUnit\Framework\Attributes\Test;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    #[Test]
    public function user_can_update_their_profile()
    {
        Storage::fake('public');

        $profileImage = UploadedFile::fake()->image('profile.jpg');

        $updatedData = [
            'fullname' => 'Updated Name',
            'username' => 'updated_username',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'profile_image' => $profileImage
        ];

        $response = $this->putJson("/api/user/{$this->token}", $updatedData);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'User updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'fullname' => 'Updated Name',
            'username' => 'updated_username',
        ]);
    }

    #[Test]
    public function user_can_change_password()
    {
        $response = $this->postJson('/api/user/change-password', [
            'current_password' => '123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Password changed successfully',
                 ]);

        $this->assertTrue(Hash::check('newpassword123', User::first()->fresh()->password));
    }
}
