<?php

namespace App\Http\Controllers\Validations;

use Illuminate\Support\Facades\Validator;

class AgreementValidation extends Validation
{
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

        return $this->validate($data, [
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "send_invoice_reminder_day" => "required|digits_between: 1,28",
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
            "invoice_reminder_recipient1" => "required",
            "invoice_reminder_recipient2" => "required",
            "invoice_receipient1" => "required|email",
            "invoice_receipient2" => "email",
            "invoice_receipient3" => "email",
            "frequency_start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
        ]);

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
        return $this->validate($data, [
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "send_invoice_reminder_day" => "required|digits_between: 1,28",
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
            "invoice_reminder_recipient1" => "required",
            "invoice_reminder_recipient2" => "required",
            "invoice_receipient1" => "required|email",
            "invoice_receipient2" => "email",
            "invoice_receipient3" => "email"
        ]);
    }

    public function validateRenew($data)
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
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "frequency_start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "send_invoice_reminder_day" => "required|digits_between: 1,28",
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
            "invoice_reminder_recipient1" => "required",
            "invoice_reminder_recipient2" => "required",
            "invoice_receipient1" => "required|email",
            "invoice_receipient2" => "email",
            "invoice_receipient3" => "email"
        ]);
    }

    public function validateOff($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "invoice_reminder_recipient1" => "required",
            "invoice_reminder_recipient2" => "required",
            "invoice_receipient1" => "required|email",
            "invoice_receipient2" => "email",
            "invoice_receipient3" => "email"
        ]);
    }

    public function validateEditforInvoiceOnOff($data)
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
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
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
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6"

        ]);
    }

    public function validateCreateforInvoiceOnOff($data)
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
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
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
            "frequency_start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/"
        ]);
    }

    public function validateRenewforInvoiceOnOff($data)
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
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "frequency_start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
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
            "final_review_day_level6" => "digits_between: 1,28|greater_than_field:initial_review_day_level6"
        ]);
    }

    public function validateOffForInvoiceOnOff($data)
    {
        return $this->validate($data, [
            "name" => "required|between:1,50",
            "start_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "end_date" => "required|regex:/\d{2}\/\d{2}\/\d{4}/",
            "valid_upto" => "required|regex:/\d{2}\/\d{2}\/\d{4}/"
        ]);
    }

}
