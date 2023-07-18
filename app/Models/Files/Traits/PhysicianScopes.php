<?php

namespace App\Models\Files\Traits;

use App\Physician;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static physicianFilesAll()
 * @method static physicianFiles(int $physicianId)
 * @method static physicianFilesByDirectory(int $physicianId, string $directory)
 */
trait PhysicianScopes
{
    /**
     * Scope a query to only include files for all physicians.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePhysicianFilesAll(Builder $query): Builder
    {
        return $query->where('fileable_type', '=', Physician::class);
    }

    /**
     * Scope a query to only include a specific physician's files.
     *
     * @param Builder $query
     * @param int $physicianId
     * @return Builder
     */
    public function scopePhysicianFiles(Builder $query, int $physicianId): Builder
    {
        return $query
            ->where('fileable_type', '=', Physician::class)
            ->where('fileable_id', '=', $physicianId);
    }

    /**
     * Scope a query to only include a specific physician's files, in a specific directory.
     *
     * @param Builder $query
     * @param int $physicianId
     * @param string $directory
     * @return Builder
     */
    public function scopePhysicianFilesByDirectory(Builder $query, int $physicianId, string $directory): Builder
    {
        return $query
            ->where('fileable_type', '=', Physician::class)
            ->where('fileable_id', '=', $physicianId)
            ->where('path', 'like', "{$directory}%");
    }
}