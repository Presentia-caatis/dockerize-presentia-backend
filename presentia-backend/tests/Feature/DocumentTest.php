<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class DocumentTest extends TestCase
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
    public function it_can_retrieve_all_documents()
    {
        Document::factory(3)->create();

        $response = $this->getJson('/api/document');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'document_name', 'path', 'created_at', 'updated_at']
                ]
            ]);
    }

#[Test]
    public function it_can_store_a_new_document()
    {
        Storage::fake('public');

        $response = $this->postJson('/api/document', [
            'document_name' => 'Test Document',
            'file' => UploadedFile::fake()->create('test.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('documents', ['document_name' => 'Test Document']);

        $document = Document::first();
        $filePath = $document->path;
        Storage::disk('public')->assertExists($filePath);
    }

    #[Test]
    public function it_can_retrieve_a_single_document()
    {
        $document = Document::factory()->create();

        $response = $this->getJson("/api/document/{$document->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $document->id,
                    'document_name' => $document->document_name,
                ],
            ]);
    }

    #[Test]
    public function it_can_update_a_document()
    {
        Storage::fake('public');
        $document = Document::factory()->create();

        $response = $this->putJson("/api/document/{$document->id}", [
            'document_name' => 'Updated Document',
            'file' => UploadedFile::fake()->create('updated.pdf', 200, 'application/pdf'),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Document updated successfully',
            ]);

        $this->assertDatabaseHas('documents', ['document_name' => 'Updated Document']);
        Storage::disk('public')->assertExists(Document::find($document->id)->path);
    }

    #[Test]
    public function it_can_delete_a_document()
    {
        Storage::fake('public');
        $document = Document::factory()->create();

        $response = $this->deleteJson("/api/document/{$document->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Document deleted successfully',
            ]);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }
}
