<?php

namespace App\Http\Controllers\Validations;

class PhysicianInterfaceValidation extends Validation
{
    public function validateCreate($data)
    {
        if ($data['interface_type_id'] == "1") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "cvi_company" => "required|numeric|between:1,9999",
                "cvi_vendor" => "required|between:1,9",
                "cvi_auth_code" => "required|alpha_num|between:1,3",
                "cvi_proc_level" => "required|alpha_num|between:1,5",
                "cvi_sep_chk_flag" => "required",
                "cvi_term_code" => "required|between:1,5",
                "cvi_rec_status" => "required",
                "cvi_posting_status" => "required",
                "cvi_bank_inst_code" => "required|alpha_num|between:1,3",
                "cvi_invc_ref_type" => "required",

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
                "cvi_company" => "required|numeric|between:1,9999",
                "cvi_vendor" => "required|between:1,9",
                "cvi_auth_code" => "required|alpha_num|between:1,3",
                "cvi_proc_level" => "required|alpha_num|between:1,5",
                "cvi_sep_chk_flag" => "required",
                "cvi_term_code" => "required|between:1,5",
                "cvi_rec_status" => "required",
                "cvi_posting_status" => "required",
                "cvi_bank_inst_code" => "required|alpha_num|between:1,3",
                "cvi_invc_ref_type" => "required",

            ]);
        } else {
            return $this->validate($data, [
                "interface_type_id" => "required",

            ]);
        }
    }
}
