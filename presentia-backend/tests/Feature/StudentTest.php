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


class StudentTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    protected $school;

    private function createStudent($data = [])
    {
        static $school; 
    
        if (!$school) {
            $school = School::factory()->create();
        }
    
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
    public function it_can_search_student_by_name()
    {
        $school = School::factory()->create(); 

        Student::create([
            'school_id' => $school->id,
            'class_group_id' => null,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'Adam',
            'gender' => 'male',
        ]);

        $response = $this->getJson('/api/student?search=Adam');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_download_student_csv()
    {
        Student::factory()->create(['student_name' => 'Alice']);
        
        $response = $this->get('/api/student/csv');
        
        $response->assertStatus(200)
                 ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    #[Test]
    public function it_can_create_a_new_student()
    {
        $school = School::factory()->create();

        $data = [
            'school_id' => $school->id,
            'class_group_id' => null,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'John Doe',
            'gender' => 'male',
        ];

        $response = $this->postJson('/api/student', $data);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Student created successfully',
            ]);

        $this->assertDatabaseHas('students', $data);
    }

    #[Test]
    public function it_can_update_a_student()
    {
        $student = Student::factory()->create();

        $data = [
            'school_id' => $student->school_id,
            'class_group_id' => $student->class_group_id,
            'nis' => '87654321',
            'nisn' => '12345678',
            'student_name' => 'Updated Name',
            'gender' => 'female',
        ];

        $response = $this->putJson("/api/student/{$student->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Student updated successfully',
                'data' => [
                    'student_name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('students', $data);
    }

    #[Test]
    public function it_can_delete_a_student()
    {
        $student = Student::factory()->create();

        $response = $this->deleteJson("/api/student/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Student deleted successfully',
            ]);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
