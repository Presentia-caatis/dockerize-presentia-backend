<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $classGroup;
    protected $student;

    // protected function setUp(): void
    // {
    //     parent::setUp();
        
    //     $user = User::factory()->create();
    //     $response = $this->postJson('/login', [
    //         'email_or_username' => $user->email,
    //         'password' => '123',  
    //     ]);

    //     $this->token = $response->json('token');

    //     $this->withHeaders([
    //         'Authorization' => 'Bearer ' . $this->token,
    //     ]);

    // }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->school = School::factory()->create([
            'school_token' => 'some_unique_token',
        ]);

        $this->classGroup = ClassGroup::factory()->create(['school_id' => $this->school->id]);
        $this->student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_group_id' => $this->classGroup->id,
        ]);
    }

    public function test_can_list_students()
    {
        $response = $this->getJson('/api/student');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status', 'message', 'data' => [['id', 'school_id', 'class_group_id', 'nis', 'nisn', 'student_name', 'gender']]
                 ]);
    }

    public function test_can_create_student()
    {
        $data = [
            'school_id' => $this->school->id,
            'class_group_id' => $this->classGroup->id,
            'nis' => '123456',
            'nisn' => '654321',
            'student_name' => 'John Doe',
            'gender' => 'male',
        ];

        $response = $this->postJson('/api/student', $data);
        
        $response->assertStatus(201)
                 ->assertJson(['status' => 'success', 'message' => 'Student created successfully']);
        $this->assertDatabaseHas('students', ['nis' => '123456']);
    }
    
    #[Test]
    public function it_can_retrieve_all_students()
    {
        $school = School::factory()->create();
        Student::factory()->count(3)->create(['school_id' => $school->id]);

        $response = $this->getJson('/api/student');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id', 'school_id', 'nis', 'nisn', 'student_name', 'gender', 'class_group_id',
                    ],
                ],
            ]);
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
    public function it_can_retrieve_a_single_student()
    {
        $student = Student::factory()->create();

        $response = $this->getJson("/api/student/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Student retrieved successfully',
                'data' => [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                ],
            ]);
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
