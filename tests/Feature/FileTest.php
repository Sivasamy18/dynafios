<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile as UploadedFile;
use Illuminate\Support\Facades\Auth;
use Dynafios\Managers\FileManager;
use Tests\TestCase;
use App\User;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private FileManager $fileManager;

    protected function setUp(): void
    {
        $this->fileManager = app(FileManager::class);
        parent::setUp();
    }

    /**
     * test download
     *
     * @return void
     */
    public function testFileCreateAndDownload()
    {
        // Initialize User
        $user = User::factory()->create()->first();

        Auth::login($user);
        $uploadedFile = new UploadedFile(
            database_path('files/test-file.txt'),
            'test-file.txt',
            'text/plain',
            null,
            false
        );

        //Create New Model & File
        $newFile = $this->fileManager->store($user, $uploadedFile, 'test');

        $response = $this->get("/file/{$newFile->id}/download");

        $this->assertDatabaseHas('files', [
            'fileable_id' => $user->id,
            'fileable_type' => get_class($user),
            'name' => $newFile->name,
            'path' => $newFile->path,
            'extension' => $newFile->extension,
            'url' => $newFile->url,
            'size' => $newFile->size,
        ]);
        $response->assertStatus(200);
    }
}