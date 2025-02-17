<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\AttendanceLateType;
use App\Models\School;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;


class AttendanceTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     $this->school = School::factory()->create();
    //     $this->classGroup = ClassGroup::factory()->create(['school_id' => $this->school->id]);
    //     $this->student = Student::factory()->create(['school_id' => $this->school->id]);
    // }

    #[Test]
    public function it_can_retrieve_all_attendances()
    {
        
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        Attendance::factory()->count(5)->create(['student_id' => $student->id]);

        $response = $this->getJson('/api/attendance');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         '*' => ['id', 'student_id', 'attendance_late_type_id', 'check_in_time', 'check_out_time']
                     ]
                 ]);
    }

    #[Test]
    public function it_can_create_an_attendance_record()
    {
        
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);

        $data = [
            'student_id' => $student->id,
            'check_in_time' => now()->toDateTimeString(),
            'check_out_time' => now()->addHours(8)->toDateTimeString(),
        ];

        $response = $this->postJson('/api/attendance', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Attendance created successfully',
                 ])
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => ['id', 'student_id', 'attendance_late_type_id', 'check_in_time', 'check_out_time']
                 ]);

        $this->assertDatabaseHas('attendances', $data);
    }

    #[Test]
    public function it_fails_to_create_attendance_with_invalid_data()
    {
        $data = [
            'student_id' => 9999,
            'check_in_time' => 'invalid_date',
            'check_out_time' => 'another_invalid_date',
        ];

        $response = $this->postJson('/api/attendance', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['student_id', 'attendance_late_type_id', 'check_in_time', 'check_out_time']);
    }

    #[Test]
    public function it_can_retrieve_a_single_attendance_record()
    {   
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
        ]);

        $response = $this->getJson("/api/attendance/{$attendance->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Attendance retrieved successfully',
                    'data' => [
                    'id' => $attendance->id,
                    'student_id' => $student->id,
                    'check_in_time' => $attendance->check_in_time->format('Y-m-d H:i:s'),
                    'check_out_time' => $attendance->check_out_time->format('Y-m-d H:i:s'),
                    'created_at' => $attendance->created_at->format('Y-m-d\TH:i:s.u\Z'), 
                    'updated_at' => $attendance->updated_at->format('Y-m-d\TH:i:s.u\Z'),
                    ]
                 ]);

    }

    #[Test]
    public function it_can_update_an_attendance_record()
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
        ]);

        $updatedData = [
            'check_out_time' => now()->addHours(8)->toDateTimeString(),
        ];

        $response = $this->putJson("/api/attendance/{$attendance->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Attendance updated successfully',
                     'data' => [
                     'id' => $attendance->id,
                     'student_id' => $student->id,
                     'check_in_time' => $attendance->check_in_time->format('Y-m-d H:i:s'),
                     'check_out_time' => \Carbon\Carbon::parse($updatedData['check_out_time'])->format('Y-m-d H:i:s'),
                     'created_at' => $attendance->created_at->format('Y-m-d\TH:i:s.u\Z'), 
                     'updated_at' => $attendance->updated_at->format('Y-m-d\TH:i:s.u\Z'),
                     ]
                 ]);

        $this->assertDatabaseHas('attendances', array_merge(['id' => $attendance->id], $updatedData));
    }

    #[Test]
    public function it_can_delete_an_attendance_record()
    {
        
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
        ]);

        $response = $this->deleteJson("/api/attendance/{$attendance->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Attendance deleted successfully'
                 ]);

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
        $this->assertDatabaseCount('attendances', 0);
    }

    #[Test]
    public function it_checks_attendance_data_is_linked_to_a_student()
    {
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id]);

        $retrievedAttendance = Attendance::with('student')->find($attendance->id);

        $this->assertNotNull($retrievedAttendance->student, "Attendance should be linked to a student.");
        $this->assertEquals($student->id, $retrievedAttendance->student->id, "The attendance's student ID should match the created student ID.");
    }

}
