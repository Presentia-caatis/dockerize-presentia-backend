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

class FingerprintTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    private function createStudent($data = [])
    {
        static $school; 
    
        if (!$school) {
            $school = School::factory()->create();
        }else{
            $school = $this->authUser->school_id;
        }

        $this->authUser->update(['school_id' => $school->id]);
    
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
    public function it_can_retrieve_student_list()
    {
        $this->createStudent();
    
        $this->assertDatabaseCount('students', 1);
    
        $response = $this->getJson('/api/student');

        $response->assertStatus(200)
        ->assertJson(['status' => 'success']);
    }

    
    #[Test]
    public function it_can_retrieve_class_groups()
    {
        $school = School::factory()->create();

        ClassGroup::factory()->count(3)->sequence(
            ['class_name' => 'Class A'],
            ['class_name' => 'Class B'],
            ['class_name' => 'Class C'],
        )->create(['school_id' => $school->id]);
        
        $this->assertDatabaseCount('class_groups', 3);

        $response = $this->getJson('/api/class-group');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['data' => []],
            ]);
    }

}
