<?php

namespace Tests\Feature;

use App\Models\AbsencePermit;
use App\Models\AbsencePermitType;
use App\Models\AbsencePermitTypeSchool;
use App\Models\Attendance;
use App\Models\Document;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class AbsencePermitTest extends TestCase
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

        $this->school = School::factory()->create();
    }

    // Absence Permit

    #[Test]
    public function it_can_retrieve_all_absence_permits()
    {

        AbsencePermit::factory(5)->create();

        $response = $this->getJson('/api/absence-permit');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'attendance_id',
                        'document_id',
                        'absence_permit_type_id',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    #[Test]
    public function it_can_create_an_absence_permit()
    {

        $attendance = Attendance::factory()->create();
        $absencePermitType = AbsencePermitType::factory()->create();

        $payload = [
            'attendance_id' => $attendance->id,
            'document_id' => null,
            'absence_permit_type_id' => $absencePermitType->id,
            'description' => 'Sakit',
        ];

        $response = $this->postJson('/api/absence-permit', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);
    }

    #[Test]
    public function it_can_update_an_absence_permit()
    {

        $absencePermit = AbsencePermit::factory()->create();

        $payload = ['description' => 'Updated description'];

        $response = $this->putJson("/api/absence-permit/{$absencePermit->id}", $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('absence_permits', ['id' => $absencePermit->id, 'description' => 'Updated description']);
    }

    #[Test]
    public function it_can_delete_an_absence_permit()
    {

        $absencePermit = AbsencePermit::factory()->create();

        $response = $this->deleteJson("/api/absence-permit/{$absencePermit->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('absence_permits', ['id' => $absencePermit->id]);
    }
    
    // Absence Permit Type

    #[Test]
    public function it_can_retrieve_all_absence_permit_types()
    {

        AbsencePermitType::factory(5)->create();

        $response = $this->getJson('/api/absence-permit-type');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'permit_name', 'is_active', 'created_at', 'updated_at']
                ]
            ]);
    }

    #[Test]
    public function it_can_create_an_absence_permit_type()
    {

        $payload = [
            'permit_name' => 'Sick Leave',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/absence-permit-type', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('absence_permit_types', $payload);
    }

    #[Test]
    public function it_can_update_an_absence_permit_type()
    {

        $absencePermitType = AbsencePermitType::factory()->create();

        $payload = [
            'permit_name' => 'Updated Leave',
            'is_active' => true,
        ];

        $response = $this->putJson("/api/absence-permit-type/{$absencePermitType->id}", $payload);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('absence_permit_types', ['id' => $absencePermitType->id, 'permit_name' => 'Updated Leave']);
    }

    #[Test]
    public function it_can_delete_an_absence_permit_type()
    {

        $absencePermitType = AbsencePermitType::factory()->create();

        $response = $this->deleteJson("/api/absence-permit-type/{$absencePermitType->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('absence_permit_types', ['id' => $absencePermitType->id]);
    }
    
    // Absence Permit Type School

    #[Test]
    public function it_can_retrieve_all_absence_permit_type_schools()
    {

        AbsencePermitTypeSchool::factory(5)->create();

        $response = $this->getJson('/api/absence-permit-type-school');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'school_id', 'absence_permit_type_id', 'created_at', 'updated_at']
                ]
            ]);
    }

    #[Test]
    public function it_can_create_an_absence_permit_type_school()
    {

        $school = School::factory()->create();
        $absencePermitType = AbsencePermitType::factory()->create();

        $payload = [
            'school_id' => $school->id,
            'absence_permit_type_id' => $absencePermitType->id,
        ];

        $response = $this->postJson('/api/absence-permit-type-school', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('absence_permit_type_schools', $payload);
    }

    #[Test]
    public function it_can_delete_an_absence_permit_type_school()
    {

        $absencePermitTypeSchool = AbsencePermitTypeSchool::factory()->create();

        $response = $this->deleteJson("/api/absence-permit-type-school/{$absencePermitTypeSchool->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('absence_permit_type_schools', ['id' => $absencePermitTypeSchool->id]);
    }
}
