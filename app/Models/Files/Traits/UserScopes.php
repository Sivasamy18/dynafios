<?php

namespace App\Models\Files\Traits;

use App\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static userFilesAll()
 * @method static userFiles(int $userId)
 * @method static userFilesByDirectory(int $userId, string $directory)
 */
trait UserScopes
{
    /**
     * Scope a query to only include files for all user.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUserFilesAll(Builder $query): Builder
    {
        return $query->where('fileable_type', '=', User::class);
    }

    /**
     * Scope a query to only include a specific user's files.
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeUserFiles(Builder $query, int $userId): Builder
    {
        return $query
            ->where('fileable_type', '=', User::class)
            ->where('fileable_id', '=', $userId);
    }

    /**
     * Scope a query to only include a specific physician's files, in a specific directory.
     *
     * @param Builder $query
     * @param int $physicianId
     * @param string $directory
     * @return Builder
     */
    public function scopeUserFilesByDirectory(Builder $query, int $physicianId, string $directory): Builder
    {
        return $query
            ->where('fileable_type', '=', User::class)
            ->where('fileable_id', '=', $physicianId)
            ->where('path', 'like', "{$directory}%");
    }
}