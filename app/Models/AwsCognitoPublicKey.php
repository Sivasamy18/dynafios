<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwsCognitoPublicKey extends Model
{
    use HasFactory;
    protected $primaryKey = 'kid';
    public $incrementing = false;
    public $fillable = ['kid', 'public_key'];
}
