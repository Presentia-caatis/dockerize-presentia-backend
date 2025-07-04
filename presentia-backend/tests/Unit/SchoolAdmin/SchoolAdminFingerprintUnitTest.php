<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;
use Tests\Traits\AuthenticatesSchoolAdmin;

class SchoolAdminFingerprintUnitTest extends TestCase
{
    use AuthenticatesSchoolAdmin;

    private function createStudent($data = [])
    {
        static $school; 
    
        if (!$school) {
            $school = School::factory()->create();
        }else{
            $school = $this->schoolAdminUser->school_id;
        }

        $this->schoolAdminUser->update(['school_id' => $school->id]);
    
        $defaultData = [
            'school_id' => $school->id,
            'class_group_id' => null,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'Adam',
            'gender' => 'male',
        ];

        return $this->postJson('/api/student', array_merge($defaultData, $data));
    }

    #[Test]
    public function school_admin_can_retrieve_student_list()
    {
        $this->createStudent();
    
        $this->assertDatabaseCount('students', 1);
     
        $response = $this->getJson('/api/student?school_id=' . $this->schoolAdminUser->school_id);

        $response->assertStatus(200)
        ->assertJson(['status' => 'success']);
    }

    
    #[Test]
    public function school_admin_can_retrieve_class_groups()
    {
        $school = School::factory()->create();

        ClassGroup::factory()->count(3)->sequence(
            ['class_name' => 'Class A'],
            ['class_name' => 'Class B'],
            ['class_name' => 'Class C'],
        )->create(['school_id' => $school->id]);
        
        $this->assertDatabaseCount('class_groups', 3);

        $response = $this->getJson("/api/class-group?school_id={$school->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['data' => []],
            ]);
    }

}
