<?php

namespace App\Models\Files\Traits;

use App\Hospital;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static hospitalFilesAll()
 * @method static hospitalFiles(int $hospitalId)
 * @method static hospitalFilesByDirectory(int $hospitalId, string $directory)
 */
trait HospitalScopes
{
    /**
     * Scope a query to only include files for all hospitals.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeHospitalFilesAll(Builder $query): Builder
    {
        return $query->where('fileable_type', '=', Hospital::class);
    }

    /**
     * Scope a query to only include a specific hospital's files.
     *
     * @param Builder $query
     * @param int $hospitalId
     * @return Builder
     */
    public function scopeHospitalFiles(Builder $query, int $hospitalId): Builder
    {
        return $query
            ->where('fileable_type', '=', Hospital::class)
            ->where('fileable_id', '=', $hospitalId);
    }


    /**
     * Scope a query to only include a specific hospital's files, in a specific directory.
     *
     * @param Builder $query
     * @param int $practiceId
     * @param string $directory
     * @return Builder
     */
    public function scopeHospitalFilesByDirectory(Builder $query, int $practiceId, string $directory): Builder
    {
        return $query
            ->where('fileable_type', '=', Hospital::class)
            ->where('fileable_id', '=', $practiceId)
            ->where('path', 'like', "{$directory}%");
    }
}