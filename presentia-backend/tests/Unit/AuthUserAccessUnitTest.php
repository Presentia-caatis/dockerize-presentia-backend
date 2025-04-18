<?php

namespace Tests\Unit;

use App\Models\User;
use Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;

class AuthUserAccessUnitTest extends TestCase
{
    use RefreshDatabase, WithFaker, TestCaseHelpers;

    #[Test]
    public function test_user_can_update_profile_with_valid_data()
    {
        $targetUser = User::factory()->create();

        $payload = [
            'fullname' => 'Updated Name',
            'username' => 'updatedusername',
            'school_id' => $this->authUser->school_id,
        ];
    
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/user/{$targetUser->id}", $payload);
    
        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'User updated successfully',
                 ]);
    
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'fullname' => 'Updated Name',
            'username' => 'updatedusername',
        ]);
    }
  
    #[Test]
    public function test_user_cannot_update_profile_with_invalid_data()
    {
        $targetUser = User::factory()->create();

        $payload = [
            'fullname' => 'a',
            'username' => '',
            'email' => 'invalid-email',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/user/{$targetUser->id}", $payload);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_user_cannot_change_password_with_wrong_old_password()
    {
        $targetUser = User::factory()->create([
            'password' => bcrypt('CorrectOldPassword'),
        ]);

        $payload = [
            'old_password' => 'WrongOldPassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/user/{$targetUser->id}", $payload);

        $response->assertStatus(400)
                ->assertJson([
                    'status' => 'failed',
                    'message' => 'Old password is incorrect',
                ]);
    }

    #[Test]
    public function test_user_can_change_password_with_correct_old_password()
    {
        $targetUser = User::factory()->create([
            'password' => bcrypt('CorrectOldPassword'),
        ]);

        $payload = [
            'old_password' => 'CorrectOldPassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/user/{$targetUser->id}", $payload);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User updated successfully',
                ]);

        $this->assertTrue(Hash::check('NewPassword123!', $targetUser->fresh()->password));
    }

}
