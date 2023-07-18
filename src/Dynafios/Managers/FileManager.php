<?php

namespace Dynafios\Managers;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FileManager
{
    public function store(Model $model, $file, $uploadPath): File
    {
        $fileData = [];
        $path = Storage::put($uploadPath, $file);

        $fileData['name'] = $file->getClientOriginalName();
        $fileData['extension'] = $file->extension();
        $fileData['path'] = $path;
        $fileData['url'] = Storage::url($path);
        $fileData['size'] = $this->bytesToReadable($file->getSize());

        return $this->create($fileData, $model);
    }

    /**
     * @param int $id
     * @return File
     */
    public function get(int $id): File
    {
        return File::findOrFail($id);
    }

    /**
     * @param $fileData
     * @param Model $model
     * @return File
     */
    private function create($fileData, Model $model): File
    {
        $fileable = [
            'fileable_id' => $model->id,
            'fileable_type' => get_class($model),
        ];

        $finalData = array_merge($fileable, $fileData);

        return File::create($finalData);
    }

    /**
     * @param File $file
     * @param array $input
     *
     * @return Boolean
     */
    public function update(File $file, Array $input): bool
    {
        // Fill in the input
        $file->fill($input);
        return $file->save();
    }

    /**
     * @param File $file
     *
     * @return Boolean
     */
    public function delete(File $file): bool
    {
        $delete = $file->delete();
        Storage::delete($file->path);

        return $delete;
    }

    /**
     * @param string $filename
     * @param string $replacementString
     * @return string
     */
    public function cleanFilename(string $filename, string $replacementString = ""): string
    {
        return preg_replace("/[^A-Za-z0-9()\.]/", $replacementString, $filename);
    }

    function bytesToReadable($bytes): string
    {
        if ($bytes == 0)
            return "0.00 B";

        $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $e), 2).$s[$e];
    }
}