<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Tests\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;
use Tests\Traits\AuthenticatesSuperAdmin;


class SuperAdminStudentManagementUnitTest extends TestCase
{
    use AuthenticatesSuperAdmin;

    protected $school;

    private function createStudent($data = [])
    {
        static $school; 
    
        if (!$school) {
            $school = School::factory()->create();
        }else{
            $school = $this->superAdminUser->school_id;
        }

        $this->superAdminUser->update(['school_id' => $school->id]);

        $this->actingAsSuperAdminWithSchool($school->id); 
    
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
    public function superadmin_can_retrieve_student_list()
    {
        $this->createStudent();
    
        $this->assertDatabaseCount('students', 1);
    
        $response = $this->getJson('/api/student');

        $response->assertStatus(200)
        ->assertJson(['status' => 'success']);
    }
    

    #[Test]
    public function superadmin_can_search_student_by_name()
    {
        $schoolId = $this->superAdminUser->school_id;

        $this->actingAsSuperAdminWithSchool($schoolId); 

        Student::create([
            'school_id' => $schoolId,
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
    public function superadmin_can_create_a_new_student()
    {
        $schoolId = $this->superAdminUser->school_id;

        $this->actingAsSuperAdminWithSchool($schoolId); 

        $data = [
            'school_id' => $schoolId,
            'class_group_id' => null,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'Adam',
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
    public function superadmin_cannot_create_a_new_student_with_invalid_data()
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $data = [
            'school_id' => $schoolId,
            'class_group_id' => null,
            'nis' => '',
            'nisn' => '12345678',
            'student_name' => 'Adam',
            'gender' => 'male',
        ];

        $response = $this->postJson('/api/student', $data);

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['nis']);

        $this->assertDatabaseCount('students', 0);
    }

    #[Test]
    public function superadmin_can_update_a_student()
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $student = Student::factory()->create([
            'school_id' => $schoolId
        ]);

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
    public function superadmin_cannot_update_a_student_with_invalid_data()
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $student = Student::factory()->create([
            'school_id' => $schoolId
        ]);

        $data = [
            'school_id' => $student->school_id,
            'class_group_id' => $student->class_group_id,
            'nis' => '87654321',
            'nisn' => '12345678',
            'student_name' => 10,
            'gender' => 'female',
        ];

        $response = $this->putJson("/api/student/{$student->id}", $data);

        $response->assertStatus(422)
        ->assertJsonValidationErrors(['student_name']);
    }

    #[Test]
    public function superadmin_can_delete_a_student()
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $student = Student::factory()->create([
            'school_id' => $schoolId
        ]);

        $response = $this->deleteJson("/api/student/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Student deleted successfully',
            ]);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    #[Test] 
    public function superadmin_can_retrieve_student_active_status_statistics()
    {
        $school = School::factory()->create();
        config(['school.id' => $school->id]);

        $plan = SubscriptionPlan::factory()->create();
        $school->subscriptionPlan()->associate($plan);
        $school->update(['latest_subscription' => now()]);
        $school->save();

        $this->superAdminUser->update(['school_id' => $school->id]);
        
        $this->actingAsSuperAdminWithSchool($school->id); 

        Student::factory()->count(3)->create(['is_active' => true, 'gender' => 'male', 'school_id' => $school->id]);
        Student::factory()->count(2)->create(['is_active' => false, 'gender' => 'female', 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'active_students',
                    'inactive_students',
                ]
            ]);
    }

    #[Test]
    public function superadmin_can_filter_students_by_class()
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $classA = ClassGroup::factory()->create(['school_id' => $schoolId]);
        $classB = ClassGroup::factory()->create(['school_id' => $schoolId]);

        // Siswa yang sesuai filter
        Student::factory()->create([
            'school_id' => $schoolId,
            'class_group_id' => $classA->id,
            'student_name' => 'Adam'
        ]);

        // Siswa lain yang tidak sesuai filter
        Student::factory()->create([
            'school_id' => $schoolId,
            'class_group_id' => $classB->id,
            'student_name' => 'Budi'
        ]);

        $response = $this->getJson('/api/student?class_group_id=' . $classA->id);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'student_name' => 'Adam',
                    'class_group_id' => $classA->id,
                ])
                ->assertJsonMissing([
                    'student_name' => 'Budi',
                ]);
    }

    #[Test]
    public function superadmin_can_sort_students_by_nis(): void
    {
        $schoolId = $this->superAdminUser->school_id;
        
        $this->actingAsSuperAdminWithSchool($schoolId); 

        $classGroup = ClassGroup::factory()->create(['school_id' => $schoolId]);

        $studentA = Student::factory()->create([
            'school_id'      => $schoolId,
            'class_group_id' => $classGroup->id,
            'nis'            => '1001',
            'student_name'   => 'Adam',
        ]);

        $studentB = Student::factory()->create([
            'school_id'      => $schoolId,
            'class_group_id' => $classGroup->id,
            'nis'            => '1003',
            'student_name'   => 'Budi',
        ]);

        $studentC = Student::factory()->create([
            'school_id'      => $schoolId,
            'class_group_id' => $classGroup->id,
            'nis'            => '1002',
            'student_name'   => 'Cantika',
        ]);

        $responseAsc = $this->getJson('/api/student?sort[nis]=asc');

        $responseAsc->assertStatus(200)
                    ->assertJson([
                        'status'  => 'success',
                        'message' => 'Students retrieved successfully',
                    ]);

        $responseAsc->assertJsonPath('data.data.0.nis', $studentA->nis); // NIS 1001
        $responseAsc->assertJsonPath('data.data.1.nis', $studentC->nis); // NIS 1002
        $responseAsc->assertJsonPath('data.data.2.nis', $studentB->nis); // NIS 1003
    }

    #[Test] 
    public function superadmin_can_retrieve_total_active_student()
    {
        $school = School::factory()->create();
        config(['school.id' => $school->id]);

        $plan = SubscriptionPlan::factory()->create();
        $school->subscriptionPlan()->associate($plan);
        $school->update(['latest_subscription' => now()]);
        $school->save();

        $this->superAdminUser->update(['school_id' => $school->id]);
        
        $this->actingAsSuperAdminWithSchool($school->id); 

        Student::factory()->count(3)->create(['is_active' => true, 'gender' => 'male', 'school_id' => $school->id]);
        Student::factory()->count(2)->create(['is_active' => false, 'gender' => 'female', 'school_id' => $school->id]);

        $response = $this->getJson('/api/dashboard-statistic/static');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'active_students',
                ]
            ]);
    }

}
