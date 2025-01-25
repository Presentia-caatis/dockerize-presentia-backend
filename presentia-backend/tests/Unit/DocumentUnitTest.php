<?php

namespace Tests\Unit;

use App\Models\AbsencePermit;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class DocumentUnitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_document_has_many_absence_permits()
    {
        $document = Document::factory()->create();
        AbsencePermit::factory(3)->create(['document_id' => $document->id]);

        $this->assertCount(3, $document->absencePermits);
    }

    #[Test]
    public function it_can_create_a_document_record()
    {
        $document = Document::create([
            'document_name' => 'Test Document',
            'path' => 'documents/test.pdf',
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'document_name' => 'Test Document',
            'path' => 'documents/test.pdf',
        ]);
    }
}
