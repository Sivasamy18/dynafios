<?php

namespace App\Http\Controllers\Validations;

class HospitalInterfaceValidation extends Validation
{
    public function validateCreate($data)
    {
        if ($data['interface_type_id'] == "1") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "protocol" => "required",
                "host" => "required",
                "port" => "required|numeric",
                "username" => "required",
                "password" => "required",
                "apcinvoice_filename" => "required",
                "apcdistrib_filename" => "required",
                "api_username" => "required|alpha_dash|between:1,30",
                "api_password" => "required|between:1,30",

            ]);
        } elseif ($data['interface_type_id'] == "2") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "protocol_imagenow" => "required",
                "host_imagenow" => "required",
                "port_imagenow" => "required|numeric",
                "username_imagenow" => "required",
                "password_imagenow" => "required",
                "email" => "required|email",
                "api_username_imagenow" => "required|alpha_dash|between:1,30",
                "api_password_imagenow" => "required|between:1,30",

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
                "protocol" => "required",
                "host" => "required",
                "port" => "required|numeric",
                "username" => "required",
                "password" => "required",
                "apcinvoice_filename" => "required",
                "apcdistrib_filename" => "required",
                "api_username" => "required|alpha_dash|between:1,30",
                "api_password" => "required|between:1,30",

            ]);
        } elseif ($data['interface_type_id'] == "2") {
            return $this->validate($data, [
                "interface_type_id" => "required",
                "protocol_imagenow" => "required",
                "host_imagenow" => "required",
                "port_imagenow" => "required|numeric",
                "username_imagenow" => "required",
                "password_imagenow" => "required",
                "email" => "required|email",
                "api_username_imagenow" => "required|alpha_dash|between:1,30",
                "api_password_imagenow" => "required|between:1,3-",

            ]);
        } else {
            return $this->validate($data, [
                "interface_type_id" => "required",

            ]);
        }
    }
}
