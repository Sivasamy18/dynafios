<?php

namespace App\Http\Controllers\Validations;

class ActionValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'name' => 'required|between:1,50'//unique:actions'
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,50"//unique:actions,name,{$data['id']}"
        ]);
    }
}
