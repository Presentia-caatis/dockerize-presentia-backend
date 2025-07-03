<?php

namespace Tests\Feature;

use App\Models\Student;
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
use Tests\Traits\AuthenticatesSuperAdmin;


class SuperAdminSchoolManagementUnitTest extends TestCase
{
    use AuthenticatesSuperAdmin;

    #[Test]
    public function superadmin_can_access_managed_school_information()
    {
        $school = School::factory()->create();

        $this->superAdminUser->update(['school_id' => $school->id]);
        
        $this->actingAsSuperAdminWithSchool($school->id); 

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonFragment([
             'active_students' => 5,
            ]);
    }

    #[Test]
    public function superadmin_can_view_list_of_all_users(): void
    {
        $school1 = School::factory()->create();
        $school2 = School::factory()->create();

        $user1_school1 = User::factory()->create(['school_id' => $school1->id, 'email_verified_at' => now()]);
        $user2_school1 = User::factory()->create(['school_id' => $school1->id, 'email_verified_at' => now()]);
        $user3_school2 = User::factory()->create(['school_id' => $school2->id, 'email_verified_at' => now()]);
        $user4_no_school = User::factory()->create(['school_id' => null, 'email_verified_at' => now()]);

        $schoolAdminRole = Role::findByName('school_admin', 'web');
        $userSchoolAdmin = User::factory()->create(['school_id' => $school1->id, 'email_verified_at' => now()]);
        $userSchoolAdmin->assignRole($schoolAdminRole);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                             '*' => [
                                 'id',
                                 'fullname',
                                 'username',
                                 'email',
                                 'school_id',
                                 'profile_image_path', 
                                 'school',
                                 'roles',
                         ],
                     ]
                 ]);

        $response->assertJsonFragment(['id' => $user1_school1->id]);
        $response->assertJsonFragment(['id' => $user2_school1->id]);
        $response->assertJsonFragment(['id' => $user3_school2->id]);
        $response->assertJsonFragment(['id' => $user4_no_school->id]);
        $response->assertJsonFragment(['id' => $userSchoolAdmin->id]);

        $this->assertEquals(6, count($response->json('data')));
    }

        #[Test]
    public function superadmin_can_assign_school_staff_role_to_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/role/user/assign', [
            'user_id' => $user->id,
            'role'    => 'school_staff',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Role assigned successfully',
                     'data'    => [
                         'id'    => $user->id,
                         'roles' => [['name' => 'school_staff']],
                     ],
                 ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('school_staff'));
    }

    #[Test]
    public function superadmin_can_remove_school_staff_role_from_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('school_staff');
        $this->assertTrue($user->hasRole('school_staff'));

        $response = $this->postJson('/api/role/user/remove', [
            'user_id' => $user->id,
            'role'    => 'school_staff',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Role removed successfully',
                     'data'    => [
                         'id'    => $user->id,
                     ],
                 ]);

        $user->refresh();
        $this->assertFalse($user->hasRole('school_staff'));
    }

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

        #[Test]
    public function superadmin_can_filter_users_by_role_to_see_school_admins(): void
    {
        $school1 = School::factory()->create();
        $school2 = School::factory()->create();

        $schoolAdminRole = Role::findByName('school_admin', 'web');

        $userSchoolAdmin1 = User::factory()->create(['school_id' => $school1->id, 'email_verified_at' => now()]);
        $userSchoolAdmin1->assignRole($schoolAdminRole);

        $userSchoolAdmin2 = User::factory()->create(['school_id' => $school2->id, 'email_verified_at' => now()]);
        $userSchoolAdmin2->assignRole($schoolAdminRole);

        $userRegularStaff = User::factory()->create(['school_id' => $school1->id, 'email_verified_at' => now()]);

        $response = $this->getJson('/api/user?filter[roles.name]=school_admin');

        $response->assertStatus(200);

        $response->assertJsonFragment(['id' => $userSchoolAdmin1->id]);
        $response->assertJsonFragment(['id' => $userSchoolAdmin2->id]);
        $response->assertJsonMissing(['id' => $this->superAdminUser->id]); 
        $response->assertJsonMissing(['id' => $userRegularStaff->id]); 

        $this->assertEquals(2, count($response->json('data')));
    }

    #[Test]
    public function superadmin_can_assign_school_admin_role_to_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->postJson('/api/role/user/assign', [
            'user_id' => $user->id,
            'role'    => 'school_admin',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Role assigned successfully',
                     'data'    => [
                         'id'    => $user->id,
                         'roles' => [['name' => 'school_admin']], 
                     ],
                 ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('school_admin'));
    }

    #[Test]
    public function superadmin_can_remove_school_admin_role_from_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('school_admin');

        $this->assertTrue($user->hasRole('school_admin'));

        $response = $this->postJson('/api/role/user/remove', [
            'user_id' => $user->id,
            'role'    => 'school_admin',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Role removed successfully',
                     'data'    => [
                         'id'    => $user->id,
                     ],
                 ]);

        $user->refresh(); 
        $this->assertFalse($user->hasRole('school_admin'));
    }

}
