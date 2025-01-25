<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use App\Models\SchoolFeature;
use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class SchoolTest extends TestCase
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

        // Simpan token ke dalam properti
        $this->token = $response->json('token');

        // Menyimpan token di header untuk semua request berikutnya
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }

    // School
    #[Test]
    public function it_can_list_all_schools()
    {
        School::factory()->count(3)->create();

        $response = $this->getJson('/api/school');

        $response->assertStatus(status: 200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Schools retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_store_a_new_school()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $payload = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'school_name' => 'Test School',
            'latest_subscription' => now()->toDateString(),
            'end_subscription' => now()->addMonth()->toDateString(),
        ];

        $response = $this->postJson('/api/school', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'School created successfully',
            ]);

        $this->assertDatabaseHas('schools', $payload);
    }

    #[Test]
    public function it_can_show_a_school()
    {
        $school = School::factory()->create();

        $response = $this->getJson("/api/school/{$school->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School retrieved successfully',
                'data' => [
                    'id' => $school->id,
                    'school_name' => $school->school_name,
                ],
            ]);
    }

    #[Test]
    public function it_can_update_a_school()
    {
        $school = School::factory()->create();

        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $payload = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'school_name' => 'Updated School',
            'latest_subscription' => now()->toDateString(),
            'end_subscription' => now()->addMonth()->toDateString(),
        ];

        $response = $this->putJson("/api/school/{$school->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School updated successfully',
            ]);

        $this->assertDatabaseHas('schools', $payload);
    }

    #[Test]
    public function it_can_delete_a_school()
    {
        $school = School::factory()->create();

        $response = $this->deleteJson("/api/school/{$school->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School deleted successfully',
            ]);

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    // School Feature
    #[Test]
    public function it_can_list_all_school_features()
    {
        SchoolFeature::factory()->count(3)->create();

        $response = $this->getJson('/api/school-feature');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School features retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_store_a_new_school_feature()
    {
        $school = School::factory()->create();
        $feature = Feature::factory()->create();

        $payload = [
            'school_id' => $school->id,
            'feature_id' => $feature->id,
            'status' => true,
        ];

        $response = $this->postJson('/api/school-feature', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'School feature created successfully',
            ]);

        $this->assertDatabaseHas('school_features', $payload);
    }

    #[Test]
    public function it_can_show_a_school_feature()
    {
        $schoolFeature = SchoolFeature::factory()->create();

        $response = $this->getJson("/api/school-feature/{$schoolFeature->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School feature retrieved successfully',
            ]);
    }

    #[Test]
    public function it_can_update_a_school_feature()
    {
        $school = School::factory()->create();
        $feature = Feature::factory()->create();
        
        $schoolFeature = SchoolFeature::factory()->create([
            'school_id' => $school->id,
            'feature_id'=> $feature->id,
        ]);

        $payload = [
            'school_id' => $school->id,
            'feature_id' => $feature->id,
            'status' => false,
        ];

        $response = $this->putJson("/api/school-feature/{$schoolFeature->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School feature updated successfully',
            ]);

        $this->assertDatabaseHas('school_features', $payload);
    }

    #[Test]
    public function it_can_delete_a_school_feature()
    {
        $schoolFeature = SchoolFeature::factory()->create();

        $response = $this->deleteJson("/api/school-feature/{$schoolFeature->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'School feature deleted successfully',
            ]);

        $this->assertDatabaseMissing('school_features', ['id' => $schoolFeature->id]);
    }
}
