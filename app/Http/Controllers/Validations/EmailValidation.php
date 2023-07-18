<?php

namespace App\Http\Controllers\Validations;

class EmailValidation extends Validation
{
    public function validateEmail($data)
    {
        return $this->validate($data, [
            "subject" => "required|between:5,80",
            "body" => "required|between:20,255"
        ]);
    }

	public function validateEmailDomain($data){
		  $emaildomains= env('EMAIL_DOMAIN_REJECT_LIST');
		  return $this->validate($data, [
			 'email'      => [ 'regex:/^((?!('.$emaildomains.')).)*$/' ],
		  ]);
	 }

}
