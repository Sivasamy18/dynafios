<?php

namespace App\Http\Controllers\Validations;

class PhysicianValidation extends Validation
{
    public function validateCreate($data)
    {
        $validating_fields = [
            'first_name' => 'required|between:1,25',
            'last_name' => 'required|between:1,25',
            'email' => 'required|email|between:1,50|unique:physicians|unique:users',
            'phone' => 'required|between:1,14|regex:/\(\d{3}\) \d{3}-\d{4}/',
            'npi' => 'required|digits:10|unique:physicians',
            'practice_start_date' => 'required|regex:/\d{2}\/\d{2}\/\d{4}/',
        ];
        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        //     $validating_fields['note2'] = 'required';
        //     $validating_fields['note3'] = 'required';
        //     $validating_fields['note4'] = 'required';
        // }
        return $this->validate($data, $validating_fields);
    }

    public function validateEdit($data)
    {
        $validating_fields = [
            'first_name' => "required|between:1,25",
            'last_name' => "required|between:1,25",
            'email' => "required|email|between:1,50|unique:users|unique:physicians,email,{$data['id']}",
            'phone' => "required|between:1,14|regex:/\(\d{3}\) \d{3}-\d{4}/",
            'npi' => "required|digits:10|unique:physicians",
        ];

        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        //     $validating_fields['note2'] = 'required';
        //     $validating_fields['note3'] = 'required';
        //     $validating_fields['note4'] = 'required';
        // }

        return $this->validate($data, $validating_fields);
    }

    /*validate practice id*/
    public function validateEditPractice($data)
    {
        return $this->validate($data, [
            "change_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            'practices' => "required|integer"
        ]);
    }

    public function validateEmailEdit($data)
    {
        $validating_fields = [
            'first_name' => "required|between:1,25",
            'last_name' => "required|between:1,25",
            'email' => "required|email|between:1,50|unique:users|unique:physicians,email,{$data['id']}",
            'phone' => "required|between:1,14|regex:/\(\d{3}\) \d{3}-\d{4}/",
            'npi' => "required|digits:10",
        ];

        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        //     $validating_fields['note2'] = 'required';
        //     $validating_fields['note3'] = 'required';
        //     $validating_fields['note4'] = 'required';
        // }
        return $this->validate($data, $validating_fields);
    }

    public function validateNpiEdit($data)
    {
        $validating_fields = [
            'first_name' => "required|between:1,25",
            'last_name' => "required|between:1,25",
            'email' => "required|email|between:1,50",
            'phone' => "required|between:1,14|regex:/\(\d{3}\) \d{3}-\d{4}/",
            'npi' => "required|digits:10|unique:physicians",
        ];
        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        //     $validating_fields['note2'] = 'required';
        //     $validating_fields['note3'] = 'required';
        //     $validating_fields['note4'] = 'required';
        // }
        return $this->validate($data, $validating_fields);
    }

    public function validateOtherEdit($data)
    {
        $validating_fields = [
            'first_name' => "required|between:1,25",
            'last_name' => "required|between:1,25",
            'email' => "required|email|between:1,50",
            'phone' => "required|between:1,14|regex:/\(\d{3}\) \d{3}-\d{4}/",
            'npi' => "required|digits:10",
        ];

        // if( $invoice_type == 1) {
        //     $validating_fields['note1'] = 'required';
        //     $validating_fields['note2'] = 'required';
        //     $validating_fields['note3'] = 'required';
        //     $validating_fields['note4'] = 'required';
        // }

        return $this->validate($data, $validating_fields);
    }

    public function validatePasswordEdit($data)
    {
        return $this->validate($data, [
            'new_password' => 'between:8,20|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%&*]).*$/',
            'current_password' => 'between:8,20|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%&*]).*$/'

        ], array(
            'new_password.regex' => 'New password must be between 8 and 20 characters in length and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).',
            'current_password.regex' => 'Current password must be between 8 and 20 characters in length and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).'
        ));
    }

//physician to multiple hospital by 1254 : added validation for existing physician

    public function validateAddExistingPhysician($data)
    {
        return $this->validate($data, [
            'email' => 'required|email|between:1,50',

            // unique:physicians|unique:users

            'practice_start_date' => 'required|regex:/\d{2}\/\d{2}\/\d{4}/'
        ]);
    }
}
