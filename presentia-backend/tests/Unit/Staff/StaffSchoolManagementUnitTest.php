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
use Tests\Traits\AuthenticatesSchoolStaff;


class StaffSchoolManagementUnitTest extends TestCase
{
    use AuthenticatesSchoolStaff;

    #[Test]
    public function staff_can_access_managed_school_information()
    {
        $school = School::factory()->create();

        $this->schoolStaffUser->update(['school_id' => $school->id]);

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonFragment([
             'active_students' => 5,
            ]);
    }

    #[Test]
    public function user_can_register_as_staff_with_valid_token(): void
    {
        $this->schoolStaffUser->update(['school_id' => null]);  

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
                         'id'     => $this->schoolStaffUser->id, 
                         'school' => [
                             'id'   => $school->id,
                             'name' => $school->name,
                         ],
                     ],
                 ]);

        $this->assertDatabaseHas('users', [
            'id'        => $this->schoolStaffUser->id, 
            'school_id' => $school->id,
        ]);
    }
    
    #[Test]
    public function system_rejects_staff_registration_with_invalid_token(): void
    {
        $this->schoolStaffUser->update(['school_id' => null]);

        $response = $this->postJson('/api/user/school/assign-via-token', [
            'school_token' => 'INVALIDTOKEN',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['school_token']);

        $this->assertDatabaseHas('users', [
            'id'        => $this->schoolStaffUser->id,
            'school_id' => null,
        ]);
    }


}
