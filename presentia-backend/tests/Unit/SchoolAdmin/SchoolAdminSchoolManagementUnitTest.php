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
use Tests\Traits\AuthenticatesSchoolAdmin;


class SchoolAdminSchoolManagementUnitTest extends TestCase
{
    use AuthenticatesSchoolAdmin;

    #[Test]
    public function school_admin_can_access_managed_school_information()
    {
        $school = School::factory()->create();

        $this->schoolAdminUser->update(['school_id' => $school->id]);

        Student::factory()->count(5)->create(['is_active' => true, 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonFragment([
             'active_students' => 5,
            ]);
    }
  
    #[Test]
    public function school_admin_can_only_view_staff_from_own_school(): void
    {
        $school1 = School::factory()->create();
        $school2 = School::factory()->create();

        $staff1 = User::factory()->create(['school_id' => $school1->id]);
        $staff2 = User::factory()->create(['school_id' => $school1->id]);
        $otherSchoolStaff = User::factory()->create(['school_id' => $school2->id]);

        $admin = User::factory()->create(['school_id' => $school1->id]);
        $admin->assignRole('school_admin');

        $this->actingAs($admin);

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
                ->assertJsonFragment(['id' => $staff1->id])
                ->assertJsonFragment(['id' => $staff2->id])
                ->assertJsonMissing(['id' => $otherSchoolStaff->id]); 

        $dataCount = count($response->json('data')['data']);
        $this->assertEquals(3, $dataCount); 
    }

    #[Test]
    public function school_admin_can_assign_user_to_their_own_school(): void
    {
        $schoolId = $this->schoolAdminUser->school_id;

        $user = User::factory()->create(['email_verified_at' => now()]); 

        $response = $this->postJson("/api/user/school/assign/{$user->id}", []);
        //dd($response->json(0));
        $response->dump();

        $response->assertStatus(201)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User assigned to school successfully',
                    'data'    => [
                        'id'        => $user->id,
                        'school_id' => $schoolId, 
                        'school'    => ['id' => $schoolId],
                    ]
                ]);

        $this->assertEquals($schoolId, $user->fresh()->school_id);
    }

    #[Test]
    public function school_admin_can_remove_user_from_their_own_school(): void
    {
        $schoolId = $this->schoolAdminUser->school_id;
        $user = User::factory()->create(['school_id' => $schoolId, 'email_verified_at' => now()]);

        $response = $this->deleteJson("/api/school/remove/{$user->id}");
        $response->dump();

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'User removed from school successfully',
                ]);

        $this->assertNull($user->fresh()->school_id);
    }

}
