<?php

namespace App\Http\Controllers\Validations;

use Validator;

class ContractValidation extends Validation
{
    public function validatePerDiemUncompensatedData($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [
            "annual_max_payment" => "numeric|between:0,99999999.99",
        ];

        return $this->validate($data, $validating_fields);
    }


    public function validateCreate($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "min_hours" => "sometimes|numeric|between:0,9999.99",
            "max_hours" => "required|numeric|between:0,9999.99",
            "rate" => "required|numeric|between:0,99999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            "mandate_details" => "required|max:1",
            "manual_end_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "hours" => "required|numeric|between:0,9999.99",
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }

    public function validateEdit($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "min_hours" => "sometimes|numeric|between:0,9999.99",
            "max_hours" => "required|numeric|between:0,9999.99",
            "rate" => "required|numeric|between:0,99999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            "mandate_details" => "required|max:1",
            "edit_manual_end_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "edit_valid_upto_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "hours" => "required|numeric|between:0,9999.99",
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }

    public function validateOnCallAgreementsData($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [
            "annual_max_payment" => "numeric|between:0,99999999.99",
            "weekday_rate" => "required|numeric|between:0,9999.99",
            "weekend_rate" => "required|numeric|between:0,9999.99",
            "holiday_rate" => "required|numeric|between:0,9999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            'mandate_details' => 'required|max:1',
            "log_over_max_hour" => "required|max:1",
        ];


        return $this->validate($data, $validating_fields);
    }

    public function validateOnCallData($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [

            "On_Call_rate" => "required|numeric|between:0,9999.99",
            "called_back_rate" => "required|numeric|between:0,9999.99",
            "called_in_rate" => "required|numeric|between:0,9999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            'mandate_details' => 'required|max:1',
            "log_over_max_hour" => "required|max:1",
        ];

        return $this->validate($data, $validating_fields);
    }

    public function validateMedicalDirectership($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [
            "min_hours" => "sometimes|numeric|between:0,9999.99",
            "max_hours" => "required|numeric|between:0,9999.99",
            "annual_cap" => "required|numeric|between:0,9999.99",
            "rate" => "required|numeric|between:0,9999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            'mandate_details' => 'required|max:1',
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }

    public function validatePSA($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [
            "annual_comp" => "required|numeric|between:0,999999999.99",
            "annual_comp_fifty" => "required|numeric|between:0,999999999.99",
            "wrvu_fifty" => "required|integer",
            "annual_comp_seventy_five" => "required|numeric|between:0,999999999.99|greater_than_field:annual_comp_fifty",
            "wrvu_seventy_five" => "required|integer|greater_than_field:wrvu_fifty",
            "annual_comp_ninety" => "required|numeric|between:0,999999999.99|greater_than_field:annual_comp_seventy_five",
            "wrvu_ninety" => "required|integer|greater_than_field:wrvu_fifty|greater_than_field:wrvu_seventy_five",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            'mandate_details' => 'required|max:1',
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }

    //Chaitraly::Monthly Stipend Validation
    public function validateMonthlyStipend($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "min_hours" => "numeric|between:0,9999.99",
            "max_hours" => "numeric|between:0,9999.99",
            "rate" => "required|numeric|between:0,9999999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            "mandate_details" => "required|max:1",
            "manual_end_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "hours" => "required|numeric|between:0,9999.99",
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }


    public function validateMonthlyStipendEdit($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "min_hours" => "numeric|between:0,9999.99",
            "max_hours" => "numeric|between:0,9999.99",
            "rate" => "required|numeric|between:0,9999999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            "mandate_details" => "required|max:1",
            "edit_manual_end_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "edit_valid_upto_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "hours" => "required|numeric|between:0,9999.99",
            "log_over_max_hour" => "required|max:1",
        ];
        return $this->validate($data, $validating_fields);
    }

    public function validatePerUnit($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        $validating_fields = [
            "min_hours" => "sometimes|numeric|between:0,9999.99",
            "max_hours" => "required|numeric|between:0,9999.99",
            "annual_cap" => "required|numeric",
            "rate" => "required|numeric|between:0,9999999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            'mandate_details' => 'required|max:1',
            "units" => "required",
        ];

        return $this->validate($data, $validating_fields);
    }

    public function validateCreateRehab($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "rate" => "required|numeric|between:0,9999999.99",
            "initial_review_day_level1" => "digits_between: 1,28",
            "final_review_day_level1" => "digits_between: 1,28|greater_than_field:initial_review_day_level1",
            "initial_review_day_level2" => "digits_between: 1,28",
            "final_review_day_level2" => "digits_between: 1,28|greater_than_field:initial_review_day_level2",
            "initial_review_day_level3" => "digits_between: 1,28",
            "final_review_day_level3" => "digits_between: 1,28|greater_than_field:initial_review_day_level3",
            "initial_review_day_level4" => "digits_between: 1,28",
            "final_review_day_level4" => "digits_between: 1,28|greater_than_field:initial_review_day_level4",
            "initial_review_day_level5" => "digits_between: 1,28",
            "final_review_day_level5" => "digits_between: 1,28|greater_than_field:initial_review_day_level5",
            "initial_review_day_level6" => "digits_between: 1,28",
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6",
            "manual_end_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto_date" => "regex:/\d{2}\/\d{2}\/\d{4}/",
        ];

        return $this->validate($data, $validating_fields);
    }

    public function validateEditRehab($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });
        $validating_fields = [
            "rate" => "required|numeric|between:0,9999999.99",
        ];
        return $this->validate($data, $validating_fields);
    }

    public function recipientValidate($data)
    {
        Validator::extend('greater_than_field', function ($attribute, $value, $parameters, $validator) {
            $min_field = $parameters[0];
            $data = $validator->getData();
            $min_value = $data[$min_field];
            return $value >= $min_value;
        });

        Validator::replacer('greater_than_field', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':field', str_replace('_', ' ', $parameters[0]), $message);
        });

        return $this->validate($data, [
            "receipient1" => "required|email",
        ]);
    }

    public function contractCopyValidate($data)
    {
        return $this->validate($data, [
            "upload_contract_copy_1" => "mimes:pdf",
            "upload_contract_copy_2" => "mimes:pdf",
            "upload_contract_copy_3" => "mimes:pdf",
            "upload_contract_copy_4" => "mimes:pdf",
            "upload_contract_copy_5" => "mimes:pdf",
            "upload_contract_copy_6" => "mimes:pdf",
        ]);
    }
}


