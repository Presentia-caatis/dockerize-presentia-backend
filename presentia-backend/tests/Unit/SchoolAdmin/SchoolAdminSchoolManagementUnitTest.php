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
    public function user_can_access_managed_school_information()
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

}
