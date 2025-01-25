<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class StudentUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_fill_mass_assignable_fields()
    {
        $school = School::factory()->create();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);

        $data = [
            'school_id' => $school->id,
            'class_group_id' => $classGroup->id,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'John Doe',
            'gender' => 'male',
        ];

        $student = Student::create($data);

        $this->assertDatabaseHas('students', $data);
        $this->assertEquals('John Doe', $student->student_name);
    }

    #[Test]
    public function it_has_a_relationship_with_class_group()
    {
        $school = School::factory()->create();
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create(['class_group_id' => $classGroup->id]);

        $this->assertInstanceOf(ClassGroup::class, $student->classGroup);
        $this->assertEquals($classGroup->id, $student->classGroup->id);
    }

    #[Test]
    public function it_has_a_relationship_with_school()
    {
        $school = School::factory()->create();
        $student = Student::factory()->create(['school_id' => $school->id]);

        $this->assertInstanceOf(School::class, $student->school);
        $this->assertEquals($school->id, $student->school->id);
    }

    #[Test]
    public function it_has_a_relationship_with_attendances()
    {
        $student = Student::factory()->create();
        $attendances = \App\Models\Attendance::factory()->count(3)->create(['student_id' => $student->id]);

        $this->assertCount(3, $student->attendances);
        $this->assertInstanceOf(\App\Models\Attendance::class, $student->attendances->first());
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Student::create([
            'school_id' => null, // Required field
            'nis' => '12345678',
            'student_name' => 'Jane Doe',
        ]);
    }

    #[Test]
    public function it_validates_gender_enum()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Student::create([
            'school_id' => School::factory()->create()->id,
            'nis' => '12345678',
            'nisn' => '87654321',
            'student_name' => 'Jane Doe',
            'gender' => 'invalid', // Invalid enum value
        ]);
    }
}
