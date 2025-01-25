<?php

namespace Tests\Unit;

use App\Models\ClassGroup;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class ClassGroupUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_class_group_belongs_to_a_school()
    {
        $classGroup = ClassGroup::factory()->create();

        $this->assertInstanceOf(School::class, $classGroup->school);
    }

    #[Test]
    public function a_class_group_has_many_students()
    {
        $classGroup = ClassGroup::factory()->create();
        Student::factory(3)->create(['class_group_id' => $classGroup->id]);

        $this->assertCount(3, $classGroup->students);
    }

    #[Test]
    public function it_can_create_a_class_group()
    {
        $school = School::factory()->create();

        $classGroup = ClassGroup::create([
            'school_id' => $school->id,
            'class_name' => 'Class A',
            'amount_of_students' => 25,
        ]);

        $this->assertDatabaseHas('class_groups', [
            'id' => $classGroup->id,
            'class_name' => 'Class A',
            'amount_of_students' => 25,
        ]);
    }
}
