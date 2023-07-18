<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoClientDomain extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $fillable = [
        'name'
    ];

    public function client()
    {
        return $this->belongsTo(SsoClient::class, 'sso_client_id', 'id');
    }
}
