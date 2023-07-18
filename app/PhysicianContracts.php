<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class PhysicianContracts extends Model
{
    use HasFactory;
    protected $table = 'physician_contracts';
    protected $softDelete = true;
    
}
