<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoClient extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $fillable = [
        'client_name',
        'label',
        'identity_provider'
    ];

    public function domain()
    {
        return $this->hasMany(SsoClientDomain::class);
    }
}
