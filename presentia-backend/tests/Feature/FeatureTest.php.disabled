<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;


class FeatureTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    #[Test]
    public function it_can_retrieve_all_features()
    {
        Feature::factory()->count(5)->create();

        $response = $this->getJson('/api/feature');
        
        //dd($response->json());

        $response->assertStatus(200)
        ->assertJsonFragment([
            'status' => 'success',
            'message' => 'Features retrieved successfully',
        ])
        ->assertJsonStructure([
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'feature_name',
                        'description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function it_can_create_a_feature()
    {
        $data = [
            'feature_name' => 'New Feature',
            'description' => 'Feature description',
        ];

        $response = $this->postJson('/api/feature', $data);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Feature created successfully',
                 ])
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => ['id', 'feature_name', 'description', 'created_at', 'updated_at']
                 ]);

        $this->assertDatabaseHas('features', $data);
    }

    #[Test]
    public function it_fails_to_create_feature_with_invalid_data()
    {
        $data = [
            'feature_name' => null, 
        ];

        $response = $this->postJson('/api/feature', $data);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['feature_name']);
    }

    #[Test]
    public function it_can_retrieve_a_single_feature()
    {
        $feature = Feature::factory()->create();

        $response = $this->getJson("/api/feature/{$feature->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Feature retrieved successfully',
                     'data' => [
                         'id' => $feature->id,
                         'feature_name' => $feature->feature_name,
                         'description' => $feature->description,
                     ]
                 ]);
    }

    #[Test]
    public function it_can_update_a_feature()
    {
        $feature = Feature::factory()->create();

        $updatedData = [
            'feature_name' => 'Updated Feature',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/feature/{$feature->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Feature updated successfully',
                 ]);

        $this->assertDatabaseHas('features', array_merge(['id' => $feature->id], $updatedData));
    }

    #[Test]
    public function it_can_delete_a_feature()
    {
        $feature = Feature::factory()->create();

        $response = $this->deleteJson("/api/feature/{$feature->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Feature deleted successfully'
                 ]);

        $this->assertDatabaseMissing('features', ['id' => $feature->id]);
    }
}
