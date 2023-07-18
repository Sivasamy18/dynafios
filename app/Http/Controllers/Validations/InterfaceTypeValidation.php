<?php

namespace App\Http\Controllers\Validations;

class InterfaceTypeValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,70|unique:interface_types",
        ]);
    }

    public function validateEdit($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,70|unique:interface_types,name,{$data['id']}",
        ]);
    }
}
