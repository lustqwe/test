<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileUploadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_list_and_delete_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $uploadResponse = $this->postJson('/api/v1/files', [
            'file' => UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf'),
            'disk' => 'public',
        ]);

        $uploadResponse
            ->assertCreated()
            ->assertJsonPath('data.disk', 'public');

        $fileId = $uploadResponse->json('data.id');
        $path = $uploadResponse->json('data.path');

        Storage::disk('public')->assertExists($path);

        $this->getJson('/api/v1/files')
            ->assertOk()
            ->assertJsonFragment(['id' => $fileId]);

        $this->deleteJson("/api/v1/files/{$fileId}")
            ->assertOk();

        Storage::disk('public')->assertMissing($path);

        $this->assertDatabaseMissing('user_files', [
            'id' => $fileId,
        ]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'action' => 'file.upload',
        ]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $user->id,
            'action' => 'file.delete',
        ]);
    }
}
