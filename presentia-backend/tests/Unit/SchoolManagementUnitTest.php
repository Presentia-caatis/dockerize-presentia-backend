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


class SchoolManagementUnitTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    #[Test]
    public function user_can_register_as_staff_with_valid_token(): void
    {
        $this->authUser->update(['school_id' => null]);  

        $school = School::factory()->create([
            'school_token' => Str::upper(Str::random(10)),
        ]);

        $response = $this->postJson('/api/user/school/assign-via-token', [
            'school_token' => $school->school_token,
        ]);

        if ($response->status() === 500) {
            dd($response->json(), $response->exception->getMessage(), $response->exception->getTraceAsString());
        }

        $response->assertStatus(201)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'User assigned to school successfully',
                     'data'    => [
                         'id'     => $this->authUser->id, 
                         'school' => [
                             'id'   => $school->id,
                             'name' => $school->name,
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id'        => $this->authUser->id, 
            'school_id' => $school->id,
        ]);
    }
    
    #[Test]
    public function system_rejects_staff_registration_with_invalid_token(): void
    {
        $this->authUser->update(['school_id' => null]);

        $response = $this->postJson('/api/user/school/assign-via-token', [
            'school_token' => 'INVALIDTOKEN',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['school_token']);

        $this->assertDatabaseHas('users', [
            'id'        => $this->authUser->id,
            'school_id' => null,
        ]);
    }

    #[Test]
    public function user_can_access_managed_school_information()
    {
        $school = School::factory()->create();

        $this->authUser->update(['school_id' => $school->id]);

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonFragment([
             'active_students' => 5,
            ]);
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

}
