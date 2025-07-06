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

class SchoolAdminClassManagementUnitTest extends TestCase
{
    use AuthenticatesSchoolAdmin;

    #[Test]
    public function school_admin_can_retrieve_class_groups()
    {
        $schoolId = $this->schoolAdminUser->school_id;
        
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

    #[Test]
    public function school_admin_can_add_class_group()
    {
        $school = School::factory()->create();

        $response = $this->postJson('/api/class-group', [
            'school_id' => $school->id,
            'class_name' => 'Class 10A',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group created successfully',
            ]);

        $this->assertDatabaseHas('class_groups', [
            'school_id' => $school->id,
            'class_name' => 'Class 10A',
        ]);
    }

    #[Test]
    public function school_admin_cannot_add_class_group_with_invalid_credentials()
    {
        $school = School::factory()->create();

        $response = $this->postJson('/api/class-group', [
            'school_id' => $school->id
        ]);
    
        $response->assertStatus(422) 
                 ->assertJsonValidationErrors(['class_name']);
    
        $this->assertDatabaseCount('class_groups', 0);
    }

    #[Test]
    public function school_admin_can_update_class_group()
    {
        $school = School::factory()->create(); 
        $this->schoolAdminUser->update(['school_id' => $school->id]);

        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);

        $response = $this->putJson("/api/class-group/{$classGroup->id}", [
            'school_id' => $school->id,
            'class_name' => 'Updated Class Name',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group updated successfully',
            ]);

        $this->assertDatabaseHas('class_groups', [
            'id' => $classGroup->id,
            'class_name' => 'Updated Class Name',
        ]);
    }
    #[Test]
    public function school_admin_cannot_update_class_group_with_invalid_credentials()
    {
        $school = School::factory()->create(); 
        $this->schoolAdminUser->update(['school_id' => $school->id]);
    
        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);
    
        $response = $this->putJson("/api/class-group/{$classGroup->id}", [
            'school_id' => $school->id,
            'class_name' => '', 
        ]);
    
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['class_name']);
    
        $this->assertDatabaseHas('class_groups', [
            'id' => $classGroup->id,
            'class_name' => $classGroup->class_name,
        ]);

    }

    #[Test]
    public function school_admin_can_delete_class_group()
    {
        $school = School::factory()->create();
        $this->schoolAdminUser->update(['school_id' => $school->id]);

        $classGroup = ClassGroup::factory()->create(['school_id' => $school->id]);

        $response = $this->deleteJson("/api/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group deleted successfully',
            ]);

        $this->assertDatabaseMissing('class_groups', [
            'id' => $classGroup->id,
        ]);
    }
}
