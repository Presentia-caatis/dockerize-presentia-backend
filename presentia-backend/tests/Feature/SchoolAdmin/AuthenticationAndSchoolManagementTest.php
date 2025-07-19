<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use PHPUnit\Framework\Attributes\Test; 
use Carbon\Carbon; // Untuk Carbon::today()

class AuthenticationAndSchoolManagementTest extends TestCase
{
    use WithFaker, TestCaseHelpers;

    private function registerAndGetUser(): array
    {
        $payload = [
            'fullname'              => 'Adam Admin',
            'username'              => 'adamAdmin',
            'email'                 => 'adamadmin@gmail.com', 
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'google_id'             => $this->faker->numerify('##############'),
        ];

        $this->postJson('/api/register', $payload)
             ->assertStatus(200)
             ->assertJsonStructure(['user' => ['id', 'email']]);

        $user = User::where('email', $payload['email'])->first();
        $user->forceFill(['email_verified_at' => now()])->save(); 
        
        return ['payload' => $payload, 'user' => $user];
    }

    private function loginUser(string $email_or_username, string $password): string
    {
        $loginRes = $this->postJson('/api/login', [
            'email_or_username' => $email_or_username,
            'password'          => $password,
        ])->assertStatus(200)
          ->assertJsonStructure(['token']);

        return $loginRes->json('token');
    }

    #[Test]
    public function user_login_becomes_school_admin_and_accesses_school_dashboard(): void
    {
        // --- 0. Persiapan Awal ---
        
        $school = School::factory()->create([
            'school_token' => Str::upper(Str::random(10)),
            'timezone' => 'Asia/Jakarta' 
        ]);

        $subscriptionPlan = \App\Models\SubscriptionPlan::factory()->create([
            'billing_cycle_month' => 6,
            'price' => 84,
        ]);
        $school->update([
            'subscription_plan_id' => $subscriptionPlan->id,
            'latest_subscription' => now()->subMonths(1)->format('Y-m-d H:i:s'),
        ]);


        // --- 1. Registrasi dan Login User Biasa ---
        $userData = $this->registerAndGetUser(); 
        $userAdminSekolah = $userData['user'];
        $userAdminSekolahPayload = $userData['payload'];

        $tokenUserAdminSekolah = $this->loginUser($userAdminSekolahPayload['email'], $userAdminSekolahPayload['password']);

        // --- 2. User Biasa Input Token Sekolah ---
        
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokenUserAdminSekolah,
        ])->postJson('/api/user/school/assign-via-token', [
            'school_token' => $school->school_token,
        ])->assertStatus(201)
          ->assertJson([
              'status'  => 'success',
              'message' => 'User assigned to school successfully',
              'data'    => [
                  'id'     => $userAdminSekolah->id,
                  'school' => ['id' => $school->id],
              ],
          ]);

        $this->assertDatabaseHas('users', [
            'id'        => $userAdminSekolah->id,
            'school_id' => $school->id,
        ]);


        // --- 3. Superadmin Assign User ke Role Admin Sekolah ---
        
        // $this->actingAs($this->authUser, 'sanctum'); 

        // $response = $this->postJson("/api/user/school/assign/{$userAdminSekolah->id}", [
        //     'user_id' => $userAdminSekolah->id,
        //     'role'    => 'school_admin',
        // ]);
        // $response->dd();

        // $response->assertStatus(200)
        //          ->assertJson([
        //              'status'  => 'success',
        //              'message' => 'Role assigned successfully',
        //              'data'    => [
        //                  'id'    => $userAdminSekolah->id,
        //                  'roles' => [['name' => 'school_admin']],
        //              ],
        //          ]);

        $schoolAdminRole = Role::findByName('school_admin', 'web');
        $userAdminSekolah->assignRole($schoolAdminRole);
        $userAdminSekolah->refresh(); 
        $this->assertTrue($userAdminSekolah->hasRole('school_admin'));


        // --- 4. User (Admin Sekolah) Membuka Dashboard Sekolah ---
        
        $userAdminSekolah->refresh(); 
        
        $this->actingAs($userAdminSekolah, 'sanctum');

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message',
                     'data' => [
                         'active_students', 'inactive_students', 'male_students', 'female_students',
                         'subscription_packet', 'is_subscription_packet_active',
                     ],
                 ]);
    }
}