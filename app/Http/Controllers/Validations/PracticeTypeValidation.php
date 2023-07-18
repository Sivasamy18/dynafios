<?php

namespace App\Http\Controllers\Validations;

class PracticeTypeValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,50|unique:practice_types",
            "description" => "required|between:1,255"
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,50|unique:practice_types,name,{$data['id']}",
            "description" => "required|between:1,255"
        ]);
    }
}
