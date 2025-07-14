<?php

namespace Tests\Feature;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SuperAdminAuthAndSchoolManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function superadmin_can_create_school_and_access_dashboard(): void
    {
        // --- 0. Setup permission dan role ---
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $schoolAdminRole = Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);

        $permissions = [
            'manage_school_users', 'basic_school', 'manage_students',
            'manage_schools', 'manage_attendance', 'assign_roles'
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $superAdminRole->givePermissionTo($permissions);

        // --- 1. Buat user superadmin dan login ---
        $superAdmin = User::factory()->create([
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

        $loginRes = $this->postJson('/api/login', [
            'email_or_username' => $superAdmin->email,
            'password' => 'password123',
        ])->assertStatus(200);

        $token = $loginRes->json('token');
        $authHeader = ['Authorization' => 'Bearer ' . $token];

        // --- 2. Buat school_admin user ---
        $schoolAdmin = User::factory()->create([
            'email_verified_at' => now(),
            'school_id' => null,
        ]);
        $schoolAdmin->assignRole('school_admin');

        // --- 3. Buat subscription plan dan fake logo ---
        $plan = SubscriptionPlan::factory()->create([
            'billing_cycle_month' => 6,
            'price' => 100,
        ]);
        Storage::fake('public');

        // --- 4. Superadmin buat sekolah ---
        $schoolPayload = [
            'name' => 'SMK Baru',
            'address' => 'Jl. Pendidikan No. 99',
            'timezone' => 'Asia/Jakarta',
            'logo_image' => UploadedFile::fake()->image('logo.jpg'),
            'user_id' => $schoolAdmin->id
        ];

        $schoolResponse = $this->withHeaders($authHeader)->postJson('/api/school', $schoolPayload);
        $schoolResponse->dump();

        $schoolResponse->assertStatus(201)
                       ->assertJson([
                           'status' => 'success',
                           'message' => 'School created successfully',
                       ]);

        $this->assertDatabaseHas('schools', [
            'name' => 'SMK Baru',
            'address' => 'Jl. Pendidikan No. 99',
            'timezone' => 'Asia/Jakarta',
        ]);

        $createdSchool = School::where('name', 'SMK Baru')->first();
        Storage::disk('public')->assertExists($createdSchool->logo_image_path);

        // --- 5. Superadmin akses dashboard sekolah baru ---
        $dashboardResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'School-Id' => $createdSchool->id,
        ])->getJson('/api/dashboard-statistic/static');

        $dashboardResponse->assertStatus(200)
                          ->assertJson([
                              'status' => 'success',
                              'message' => 'Static dashboard statistics retrieved successfully',
                          ]);
    }
}