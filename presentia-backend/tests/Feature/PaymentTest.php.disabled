<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Payment;
use App\Models\School;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCaseHelpers;


class PaymentTest extends TestCase
{
    use RefreshDatabase, TestCaseHelpers;

    #[Test]
    public function it_can_list_all_payments()
    {
        Payment::factory()->count(3)->create();

        $response = $this->getJson('/api/payment');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payments retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_store_a_new_payment()
    {
        $school = School::factory()->create();
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $payload = [
            'school_id' => $school->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'credit_card',
            'amount' => 5000,
            'status' => 'completed',
        ];

        $response = $this->postJson('/api/payment', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment created successfully',
            ]);

        $this->assertDatabaseHas('payments', $payload);
    }

    #[Test]
    public function it_can_show_a_payment()
    {
        $payment = Payment::factory()->create();

        $response = $this->getJson("/api/payment/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment retrieved successfully',
                'data' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                ],
            ]);
    }

    #[Test]
    public function it_can_update_a_payment()
    {
        $school = School::factory()->create();

        $payment = Payment::factory()->create([
            'school_id' => $school->id,
        ]);

        $payload = [
            'school_id' => $school->id,
            'subscription_plan_id' => $payment->subscriptionPlan->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Bank Transfer',
            'amount' => 7000,
            'status' => 'completed',
        ];

        $response = $this->putJson("/api/payment/{$payment->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment updated successfully',
                'data' => [
                    'id' => $payment->id,
                    'amount' => 7000,
                ],
            ]);

        $this->assertDatabaseHas('payments', $payload);
    }

    #[Test]
    public function it_can_delete_a_payment()
    {
        $payment = Payment::factory()->create();

        $response = $this->deleteJson("/api/payment/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment deleted successfully',
            ]);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }
}
