<?php

namespace App\Http\Controllers\Validations;

class PracticeValidation extends Validation
{
    public function validateCreate($data)
    {
        $validating_fields = [
            'name' => 'required|between:1,50|unique:practices',
            'npi' => 'required|digits:10'
        ];

        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        // }
        return $this->validate($data, $validating_fields);
    }

    public function validateEdit($data)
    {
        $validating_fields = [
            'name' => "required|between:1,50|unique:practices,name,{$data['id']}",
            'npi' => 'required|digits:10'
        ];

        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        // }
        return $this->validate($data, $validating_fields);
    }

    public function validateAddManager($data)
    {
        return $this->validate($data, [
            'email' => 'required|email|exists:users'
        ]);
    }
}