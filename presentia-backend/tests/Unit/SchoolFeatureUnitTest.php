<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\SchoolFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;


class SchoolFeatureUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_school()
    {
        $schoolFeature = SchoolFeature::factory()->create();

        $this->assertNotNull($schoolFeature->school);
    }

    #[Test]
    public function it_belongs_to_feature()
    {
        $schoolFeature = SchoolFeature::factory()->create();

        $this->assertNotNull($schoolFeature->feature);
    }
}
