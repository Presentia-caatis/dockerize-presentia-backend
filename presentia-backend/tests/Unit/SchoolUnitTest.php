<?php

namespace Tests\Unit;

use Str;
use Tests\TestCase;
use App\Models\School;
use App\Models\SchoolFeature;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionHistory;
use App\Models\Payment;
use App\Models\AbsencePermitType;
use App\Models\AttendanceLateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class SchoolUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_fill_mass_assignable_fields()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();

        $data = [
            'subscription_plan_id' => $subscriptionPlan->id,
            'name' => 'Test School',
            'address' => 'Unknown',
            'latest_subscription' => now()->toDateString(),
            'school_token' => Str::random(),
        ];

        $school = School::create($data);

        $this->assertDatabaseHas('schools', $data);
        $this->assertEquals($data['name'], $school->name);
    }

    #[Test]
    public function it_has_a_relationship_with_subscription_plan()
    {
        $subscriptionPlan = SubscriptionPlan::factory()->create();
        $school = School::factory()->create(['subscription_plan_id' => $subscriptionPlan->id]);

        $this->assertInstanceOf(SubscriptionPlan::class, $school->subscriptionPlan);
        $this->assertEquals($subscriptionPlan->id, $school->subscriptionPlan->id);
    }

    #[Test]
    public function it_has_a_relationship_with_school_features()
    {
        $school = School::factory()->create();
        $schoolFeatures = SchoolFeature::factory()->count(3)->create(['school_id' => $school->id]);

        $this->assertCount(3, $school->schoolFeatures);
        $this->assertInstanceOf(SchoolFeature::class, $school->schoolFeatures->first());
    }

    #[Test]
    public function it_has_a_relationship_with_subscription_histories()
    {
        $school = School::factory()->create();
        $subscriptionHistories = SubscriptionHistory::factory()->count(2)->create(['school_id' => $school->id]);

        $this->assertCount(2, $school->subscriptionHistories);
        $this->assertInstanceOf(SubscriptionHistory::class, $school->subscriptionHistories->first());
    }

    #[Test]
    public function it_has_a_relationship_with_payments()
    {
        $school = School::factory()->create();
        $payments = Payment::factory()->count(4)->create(['school_id' => $school->id]);

        $this->assertCount(4, $school->payments);
        $this->assertInstanceOf(Payment::class, $school->payments->first());
    }

    #[Test]
    public function it_has_a_relationship_with_absence_permit_types()
    {
        $school = School::factory()->create();
        $absencePermitTypes = AbsencePermitType::factory()->count(2)->create();

        $school->absencePermitTypes()->attach($absencePermitTypes);

        $this->assertCount(2, $school->absencePermitTypes);
        $this->assertInstanceOf(AbsencePermitType::class, $school->absencePermitTypes->first());
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        $subscriptionPlan = SubscriptionPlan::factory()->create();

        School::create([
            'subscription_plan_id' => $subscriptionPlan->id,
            'school_name' => null,
        ]);
    }

    #[Test]
    public function it_fails_when_subscription_plan_id_is_null()
    {
        $data = [
            'subscription_plan_id' => null, // Null value for a NOT NULL column
            'name' => 'Nullable Plan School',
            'latest_subscription' => now()->toDateString(),
        ];

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage("Column 'subscription_plan_id' cannot be null");

        // Attempt to create a school with a null subscription_plan_id
        School::create($data);

        // Ensure no record is created in the database
        $this->assertDatabaseMissing('schools', ['name' => 'Nullable Plan School']);
    }

    #[Test]
    public function it_checks_date_fields_format()
    {
        $school = School::factory()->create([
            'latest_subscription' => '2024-10-01',
        ]);

        $this->assertEquals('2024-10-01', $school->latest_subscription);
    }

    #[Test]
    public function it_checks_relationship_cascade_delete()
    {
        $school = School::factory()->create();
        $schoolFeature = SchoolFeature::factory()->create(['school_id' => $school->id]);

        $school->delete();

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        $this->assertDatabaseMissing('school_features', ['id' => $schoolFeature->id]);
    }
}
