<?php

namespace App\Http\Controllers\Files;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Controller;
use Dynafios\Managers\FileManager;

class FileController extends Controller
{

    /**
     * Download File by its ID.
     *
     * @param FileManager $fileManager
     * @param int $fileId
     * @return StreamedResponse
     */
    public function download(FileManager $fileManager, int $fileId): StreamedResponse
    {
        $file = $fileManager->get($fileId);
        return Storage::download($file->path, $file->name);
    }

    /**
     * @param FileManager $fileManager
     * @param int $fileId
     * @return string
     */
    public function show(FileManager $fileManager, int $fileId): string
    {
        $file = $fileManager->get($fileId);
        return Storage::url($file->url);
    }
}
