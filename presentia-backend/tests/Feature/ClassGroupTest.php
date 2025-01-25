<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class ClassGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $response = $this->postJson('/login', [
            'email_or_username' => $user->email,
            'password' => '123',  
        ]);

        $this->token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);

    }

    #[Test]
    public function it_can_retrieve_all_class_groups()
    {

        School::factory()->create();
        ClassGroup::factory(5)->create();

        $response = $this->getJson('/api/class-group');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'school_id', 'class_name', 'amount_of_students', 'created_at', 'updated_at']
                ]
            ]);
    }

    #[Test]
    public function it_can_create_a_class_group()
    {

        $school = School::factory()->create();
        $payload = [
            'school_id' => $school->id,
            'class_name' => 'Class A',
            'amount_of_students' => 30,
        ];

        $response = $this->postJson('/api/class-group', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group created successfully',
                'data' => $payload,
            ]);

        $this->assertDatabaseHas('class_groups', $payload);
    }

    #[Test]
    public function it_can_retrieve_a_specific_class_group()
    {

        $classGroup = ClassGroup::factory()->create();

        $response = $this->getJson("/api/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group retrieved successfully',
                'data' => [
                    'id' => $classGroup->id,
                    'class_name' => $classGroup->class_name,
                ],
            ]);
    }

    #[Test]
    public function it_can_update_a_class_group()
    {

        $school = School::factory()->create();

        $classGroup = ClassGroup::factory()->create([
            'school_id' => $school->id,  
        ]);
        $payload = [
            'school_id' => $school->id,
            'class_name' => 'Updated Class Name',
            'amount_of_students' => 99,
        ];

        $response = $this->putJson("/api/class-group/{$classGroup->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group updated successfully',
            ]);

        $this->assertDatabaseHas('class_groups', ['id' => $classGroup->id, 'class_name' => 'Updated Class Name']);
    }

    #[Test]
    public function it_can_delete_a_class_group()
    {

        $classGroup = ClassGroup::factory()->create();

        $response = $this->deleteJson("/api/class-group/{$classGroup->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Class group deleted successfully',
            ]);

        $this->assertDatabaseMissing('class_groups', ['id' => $classGroup->id]);
    }
}
