<?php

namespace App\Http\Controllers\Validations;

class ContractInterfaceValidation extends Validation
{
    public function validateCreate($data)
    {
        if ($data['interface_type_id'] == "1") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "cvd_company" => "required|numeric|between:1,9999",
                "cvd_vendor" => "required|between:1,9",
                "invoice_number_suffix" => "between:1,15",
                "cvd_dist_company" => "required|numeric|between:1,9999",
                "cvd_dis_acct_unit" => "required|alpha_num|between:1,15",
                "cvd_dis_account" => "required|numeric|between:1,999999",
                "cvd_dis_sub_acct" => "alpha_num|between:1,4",

            ]);
        } else {
            return $this->validate($data, [
                "interface_type_id" => "required",

            ]);
        }

    }

    public function validateEdit($data)
    {
        if ($data['interface_type_id'] == "1") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "cvd_company" => "required|numeric|between:1,9999",
                "cvd_vendor" => "required|between:1,9",
                "invoice_number_suffix" => "between:1,15",
                "cvd_dist_company" => "required|numeric|between:1,9999",
                "cvd_dis_acct_unit" => "required|alpha_num|between:1,15",
                "cvd_dis_account" => "required|numeric|between:1,999999",
                "cvd_dis_sub_acct" => "alpha_num|between:1,4",

            ]);
        } else {
            return $this->validate($data, [
                "interface_type_id" => "required",

            ]);
        }
    }
}
