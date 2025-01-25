<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Payment;
use App\Models\School;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class PaymentUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_fillable_attributes()
    {
        $payment = new Payment();
        $this->assertEquals(
            ['school_id', 'subscription_plan_id', 'payment_date', 'payment_method', 'amount', 'status'],
            $payment->getFillable()
        );
    }

    #[Test]
    public function it_belongs_to_a_school()
    {
        $school = School::factory()->create();
        $payment = Payment::factory()->create(['school_id' => $school->id]);

        $this->assertEquals($school->id, $payment->School->id);
    }

    #[Test]
    public function it_belongs_to_a_subscription_plan()
    {
        $plan = SubscriptionPlan::factory()->create();
        $payment = Payment::factory()->create(['subscription_plan_id' => $plan->id]);

        $this->assertEquals($plan->id, $payment->subscriptionPlan->id);
    }
}
