<?php

namespace App\Models\Files\Traits;

use App\Practice;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static practiceFilesAll()
 * @method static practiceFiles(int $practiceId)
 * @method static practiceFilesByDirectory(int $practiceId, string $directory)
 */
trait PracticeScopes
{
    /**
     * Scope a query to only include files for all practices.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePracticeFilesAll(Builder $query): Builder
    {
        return $query->where('fileable_type', '=', Practice::class);
    }

    /**
     * Scope a query to only include a specific practice's files.
     *
     * @param Builder $query
     * @param int $practiceId
     * @return Builder
     */
    public function scopePracticeFiles(Builder $query, int $practiceId): Builder
    {
        return $query
            ->where('fileable_type', '=', Practice::class)
            ->where('fileable_id', '=', $practiceId);
    }

    /**
     * Scope a query to only include a specific practice's files, in a specific directory.
     *
     * @param Builder $query
     * @param int $practiceId
     * @param string $directory
     * @return Builder
     */
    public function scopePracticeFilesByDirectory(Builder $query, int $practiceId, string $directory): Builder
    {
        return $query
            ->where('fileable_type', '=', Practice::class)
            ->where('fileable_id', '=', $practiceId)
            ->where('path', 'like', "{$directory}%");
    }
}