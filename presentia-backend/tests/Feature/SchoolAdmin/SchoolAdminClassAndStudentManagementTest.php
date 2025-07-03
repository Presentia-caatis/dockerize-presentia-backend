<?php

namespace Tests\Feature;

use App\Models\ClassGroup; // Import model ClassGroup
use App\Models\Student;    // Import model Student
use App\Models\School;     // Import model School
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\TestCaseHelpers;
use Tests\Traits\AuthenticatesSchoolAdmin;

class SchoolAdminClassAndStudentManagementTest extends TestCase
{
    use WithFaker, AuthenticatesSchoolAdmin;

    #[Test]
    public function student_class_management(): void
    {
        // --- 0. Initial Setup ---
        $schoolId = $this->schoolAdminUser->school_id;


        // --- 1. Tambah Siswa ---
        $studentPayload = [
            'school_id'      => $schoolId,
            'class_group_id' => null, 
            'nis'            => 'S' . $this->faker->unique()->randomNumber(8),
            'nisn'           => 'N' . $this->faker->unique()->randomNumber(8),
            'student_name'   => $this->faker->name,
            'gender'         => $this->faker->randomElement(['male', 'female']),
        ];

        $response = $this->postJson('/api/student', $studentPayload);

        $response->assertStatus(201)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Student created successfully',
                 ]);

        $createdStudentId = $response->json('data.id');
        $this->assertDatabaseHas('students', [
            'id'             => $createdStudentId,
            'school_id'      => $schoolId,
            'nis'            => $studentPayload['nis'],
            'student_name'   => $studentPayload['student_name'],
        ]);


        // --- 2. Tambah Kelas ---
        $classGroupPayload = [
            'school_id'  => $schoolId,
            'class_name' => 'Class ' . $this->faker->unique()->word() . ' ' . $this->faker->numerify('##'),
        ];

        $response = $this->postJson('/api/class-group', $classGroupPayload);

        $response->assertStatus(201)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Class group created successfully',
                 ]);

        $createdClassGroupId = $response->json('data.id');
        $this->assertDatabaseHas('class_groups', [
            'id'         => $createdClassGroupId,
            'school_id'  => $schoolId,
            'class_name' => $classGroupPayload['class_name'],
        ]);


        // --- 3. Update Kelas Siswa ---
        $updateStudentPayload = [
            'class_group_id' => $createdClassGroupId,
        ];

        $response = $this->putJson("/api/student/{$createdStudentId}", $updateStudentPayload);

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Student updated successfully',
                 ]);

        $this->assertDatabaseHas('students', [
            'id'             => $createdStudentId,
            'class_group_id' => $createdClassGroupId,
        ]);


        // --- 4. Tampilkan Siswa dengan Filtering Kelas ---
        $response = $this->getJson("/api/student?class_group_id={$createdClassGroupId}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status'  => 'success',
                     'message' => 'Students retrieved successfully',
                 ])
                 ->assertJsonFragment([
                     'id'             => $createdStudentId,
                     'class_group_id' => $createdClassGroupId,
                     'student_name'   => $studentPayload['student_name'],
                 ]);

        $studentWithoutClass = Student::factory()->create([
            'school_id' => $schoolId,
            'class_group_id' => null,
            'student_name' => 'Adam',
        ]);

        $responseFiltered = $this->getJson("/api/student?class_group_id={$createdClassGroupId}");
        
        $responseFiltered->assertJsonMissing([
            'id' => $studentWithoutClass->id,
            'student_name' => 'Adam',
        ]);
        
        $responseFiltered->assertJsonCount(1, 'data.data');
    }
}
