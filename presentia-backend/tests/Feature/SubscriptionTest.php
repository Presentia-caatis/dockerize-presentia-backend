<?php

namespace Tests\Feature;

use App\Models\SubscriptionFeature;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPlan;
use App\Models\Feature;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $response = $this->postJson('/api/login', [
            'email_or_username' => $user->email,
            'password' => '123',
        ]);

        $this->token = $response->json('token');

        // Set token in headers for following requests
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }

    #[Test]
    public function it_can_retrieve_all_subscription_features()
    {
        SubscriptionFeature::factory()->count(5)->create();
        $response = $this->getJson('/api/subscription-feature');
        
        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function it_can_create_a_subscription_feature()
    {
        $feature = Feature::factory()->create();
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $data = [
            'feature_id' => $feature->id,
            'subscription_plan_id' => $subscriptionPlan->id,
        ];

        $response = $this->postJson('/api/subscription-feature', $data);

        $response->assertStatus(201)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription feature created successfully']);
    }

    #[Test]
    public function it_can_show_a_subscription_feature()
    {
        $subscriptionFeature = SubscriptionFeature::factory()->create();

        $response = $this->getJson("/api/subscription-feature/{$subscriptionFeature->id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription feature retrieved successfully']);
    }

    #[Test]
    public function it_can_delete_a_subscription_feature()
    {
        $subscriptionFeature = SubscriptionFeature::factory()->create();

        $response = $this->deleteJson("/api/subscription-feature/{$subscriptionFeature->id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription feature deleted successfully']);
    }

    #[Test]
    public function it_returns_error_if_subscription_feature_not_found()
    {
        $response = $this->getJson('/api/subscription-feature/999');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'error' => 'No query results for model [App\\Models\\SubscriptionFeature] 999'
            ]);
    }

    //Subscription History

        #[Test]
        public function it_can_retrieve_all_subscription_histories()
        {
            SubscriptionHistory::factory()->count(5)->create();
            $response = $this->getJson('/api/subscription-history');
            
            $response->assertStatus(200)
                     ->assertJson(['status' => 'success'])
                     ->assertJsonCount(5, 'data');
        }
    
        #[Test]
        public function it_can_create_a_subscription_history()
        {
            $school = school::factory()->create();
            $subscriptionPlan = SubscriptionPlan::factory()->create();
    
            $data = [
                'school_id' => $school->id,
                'subscription_plan_id' => $subscriptionPlan->id,
            ];
    
            $response = $this->postJson('/api/subscription-history', $data);
    
            $response->assertStatus(201)
                     ->assertJson(['status' => 'success', 'message' => 'Subscription history created successfully']);
        }
    
        #[Test]
        public function it_can_show_a_subscription_history()
        {
            $subscriptionHistory = SubscriptionHistory::factory()->create();
    
            $response = $this->getJson("/api/subscription-history/{$subscriptionHistory->id}");
    
            $response->assertStatus(200)
                     ->assertJson(['status' => 'success', 'message' => 'Subscription history retrieved successfully']);
        }
    
        #[Test]
        public function it_can_delete_a_subscription_history()
        {
            $subscriptionHistory = SubscriptionHistory::factory()->create();
    
            $response = $this->deleteJson("/api/subscription-history/{$subscriptionHistory->id}");
    
            $response->assertStatus(200)
                     ->assertJson(['status' => 'success', 'message' => 'Subscription history deleted successfully']);
        }
    
        #[Test]
        public function it_returns_error_if_subscription_history_not_found()
        {
            $response = $this->getJson('/api/subscription-history/999');
    
            $response->assertStatus(404)
                ->assertJsonFragment([
                    'error' => 'No query results for model [App\\Models\\SubscriptionHistory] 999'
                ]);
        }

    //Subcsription Plans

    #[Test]
    public function it_can_retrieve_all_subscription_plans()
    {
        SubscriptionPlan::factory()->count(5)->create();
        $response = $this->getJson('/api/subscription-plan');
        
        $response->assertStatus(200)
                 ->assertJson(['status' => 'success'])
                 ->assertJsonCount(5, 'data');
    }

    #[Test]
    public function it_can_create_a_subscription_plan()
    {
        $data = [
            'subscription_name' => 'Basic Plan',
            'billing_cycle_month' => 'monthly',
            'price' => 1000,
        ];

        $response = $this->postJson('/api/subscription-plan', $data);

        $response->assertStatus(201)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription plan created successfully']);
    }

    #[Test]
    public function it_can_show_a_subscription_plan()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $response = $this->getJson("/api/subscription-plan/{$subscriptionPlan->id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription plan retrieved successfully']);
    }

    #[Test]
    public function it_can_update_a_subscription_plan()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $data = [
            'subscription_name' => 'Updated Plan',
            'billing_cycle_month' => 'yearly',
            'price' => 2000,
        ];

        $response = $this->putJson("/api/subscription-plan/{$subscriptionPlan->id}", $data);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription plan updated successfully']);
        
        $this->assertDatabaseHas('subscription_plans', ['subscription_name' => 'Updated Plan']);
    }

    #[Test]
    public function it_can_delete_a_subscription_plan()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $response = $this->deleteJson("/api/subscription-plan/{$subscriptionPlan->id}");

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success', 'message' => 'Subscription plan deleted successfully']);
    }

    #[Test]
    public function it_returns_error_if_subscription_plan_not_found()
    {
        $response = $this->getJson('/api/subscription-plan/999');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'error' => 'No query results for model [App\\Models\\SubscriptionPlan] 999'
            ]);
    }
}
