<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Str;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolFeature;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class SchoolManagementUnitTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    // #[Test]
    // public function user_can_register_as_staff_with_valid_token(): void
    // {
    //     $school = School::factory()->create([
    //         'school_token' => Str::upper(Str::random(10)),
    //     ]);

    //     $user = User::factory()->create([
    //         'password'  => bcrypt('Password123!'),
    //         'school_id' => null,
    //     ]);

    //     $loginRes = $this->postJson('/api/login', [
    //         'email_or_username' => $user->email,
    //         'password'          => 'Password123!',
    //     ])->assertStatus(200)
    //       ->assertJsonStructure(['token']);

    //     $token = $loginRes->json('token');

    //     $response = $this->withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //         ])->postJson('/api/school/assign-via-token', [
    //             'school_token' => $school->school_token,
    //         ]);

    //     $response->assertStatus(201)
    //              ->assertJson([
    //                  'status'  => 'success',
    //                  'message' => 'User assigned to school successfully',
    //                  'data'    => [
    //                      'id'     => $user->id,
    //                      'school' => [
    //                          'id'   => $school->id,
    //                          'name' => $school->name,
    //                      ],
    //                  ],
    //              ]);

    //     $this->assertDatabaseHas('users', [
    //         'id'        => $user->id,
    //         'school_id' => $school->id,
    //     ]);
    // }
    
    // #[Test]
    // public function system_rejects_staff_registration_with_invalid_token(): void
    // {
    //     $user = User::factory()->create([
    //         'password' => bcrypt('Password123!'),
    //     ]);

    //     $token = $this->postJson('/api/login', [
    //         'email_or_username' => $user->email,
    //         'password'          => 'Password123!',
    //     ])->json('token');

    //     $response = $this->withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //         ])->postJson('/api/school/assign-via-token', [
    //             'school_token' => 'INVALIDTOKEN',
    //         ]);

    //     $response->assertStatus(422)
    //              ->assertJsonValidationErrors(['school_token']);

    //     $this->assertDatabaseHas('users', [
    //         'id'        => $user->id,
    //         'school_id' => null,
    //     ]);
    // }

    // #[Test]
    // public function admin_can_retrieve_role_list(): void
    // {
    //     /* ---------- Arrange ---------- */
    //     // 1. Buat role admin + permissions (jika belum)
    //     $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    //     $adminRole->givePermissionTo('manage_school_users'); // izin minimal

    //     $adminUser = User::factory()->create(['password' => bcrypt('Password123!')]);
    //     $adminUser->assignRole($adminRole);

    //     $token = $this->postJson('/api/login', [
    //         'email_or_username' => $adminUser->email,
    //         'password'          => 'Password123!',
    //     ])->json('token');

    //     Role::factory()->count(2)->create();

    //     /* ---------- Act ---------- */
    //     $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
    //                      ->getJson('/api/role?perPage=10');

    //     /* ---------- Assert ---------- */
    //     $response->assertStatus(200)
    //              ->assertJson([
    //                  'status'  => 'success',
    //                  'message' => 'Roles retrieved successfully',
    //              ])
    //              ->assertJsonStructure(['data' => ['data' => [['id','name','permissions']]]]);

    //     // Pastikan tidak kosong
    //     $this->assertGreaterThan(0, count($response->json('data.data')));
    // }

    #[Test]
    public function superadmin_can_retrieve_school_list()
    {
        School::factory()->count(3)->create();

        $response = $this->getJson('/api/school');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Schools retrieved successfully'
            ])
            ->assertJsonCount(4, 'data.data');
    }

    #[Test]
    public function superadmin_can_create_school_with_valid_data()
    {
        SubscriptionPlan::factory()->create(['billing_cycle_month' => 0]);

        $payload = [
            'name' => 'Sekolah Baru',
            'address' => 'Jl. Pendidikan No. 123',
            'timezone' => 'Asia/Jakarta',
            'logo_image' => UploadedFile::fake()->image('logo.jpg')
        ];

        $response = $this->postJson('/api/school', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'School created successfully'
            ]);

        $this->assertDatabaseHas('schools', [
            'name' => 'Sekolah Baru',
            'address' => 'Jl. Pendidikan No. 123',
            'timezone' => 'Asia/Jakarta'
        ]);

        $school = School::first();
        Storage::disk('public')->assertExists($school->logo_image_path);
    }

    #[Test]
    public function system_rejects_school_creation_with_invalid_data()
    {
        $payload = [
            'name' => '', // Nama kosong
            'address' => 'Jl. Pendidikan No. 123',
            'timezone' => 'Invalid/Timezone' // Timezone tidak valid
        ];

        $response = $this->postJson('/api/school', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'timezone']);
    }

    #[Test]
    public function superadmin_can_update_school_with_valid_data()
    {
        $school = School::factory()->create();
        Storage::fake('public');

        $payload = [
            'name' => 'Sekolah Updated',
            'address' => 'Jl. Baru No. 456',
            'logo_image' => UploadedFile::fake()->image('new-logo.jpg')
        ];

        $response = $this->putJson("/api/school/{$school->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School updated successfully'
            ]);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'name' => 'Sekolah Updated',
            'address' => 'Jl. Baru No. 456'
        ]);

        $updatedSchool = School::find($school->id);
        Storage::disk('public')->assertExists($updatedSchool->logo_image_path);
    }

    #[Test]
    public function system_rejects_school_update_with_invalid_data()
    {
        $school = School::factory()->create();

        $payload = [
            'subscription_plan_id' => 9999 // ID tidak ada
        ];

        $response = $this->putJson("/api/school/{$school->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subscription_plan_id']);
    }

    #[Test]
    public function superadmin_can_delete_school()
    {
        $school = School::factory()->create(['logo_image_path' => 'logos/test.jpg']);
        Storage::fake('public')->put('logos/test.jpg', 'dummy');

        $response = $this->deleteJson("/api/school/{$school->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School deleted successfully'
            ]);

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        
        Storage::disk('public')->assertMissing('logos/test.jpg');
    }

}
