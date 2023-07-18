<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    protected $table = "contract_templates";

    public function contractName()
    {
        return $this->belongsTo('App\ContractName');
    }

    public function contractType()
    {
        return $this->belongsTo('App\ContractType');
    }

    public function actions()
    {
        return $this->belongsToMany('App\Action')->withPivot('hours');
    }
}
