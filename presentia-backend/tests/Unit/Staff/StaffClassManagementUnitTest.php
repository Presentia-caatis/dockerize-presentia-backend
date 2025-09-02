<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\ClassGroup;
use App\Models\School;
use Tests\TestCaseHelpers;
use PHPUnit\Framework\Attributes\Test;
use Tests\Traits\AuthenticatesSchoolAdmin;
use Tests\Traits\AuthenticatesSchoolStaff;
use Tests\Traits\AuthenticatesSuperAdmin;

class StaffClassManagementUnitTest extends TestCase
{
    use AuthenticatesSchoolStaff;

    #[Test]
    public function staff_can_retrieve_class_groups()
    {
        $schoolId = $this->schoolStaffUser->school_id;

        ClassGroup::factory()->count(3)->sequence(
            ['class_name' => 'Class A'],
            ['class_name' => 'Class B'],
            ['class_name' => 'Class C'],
        )->create(['school_id' => $schoolId]);
        
        $this->assertDatabaseCount('class_groups', 3);

        $response = $this->getJson('/api/class-group?school_id=' . $schoolId);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['data' => []],
            ]);
    }
}
