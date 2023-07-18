<?php

namespace App\Models\Files;

use App\Models\Files\Traits\FileTraits;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    use FileTraits;

    protected $fillable = [
        'name',
        'url',
        'extension',
        'path',
        'size',
        'fileable_type',
        'fileable_id',
    ];

    /**
     * Get all the owning fileable models.
     */
    public function fileable()
    {
        return $this->morphTo();
    }
}
