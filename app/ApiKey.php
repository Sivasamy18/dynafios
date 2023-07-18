<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    public function physician()
    {
        return $this->belongsTo("Physician");
    }

    /**
     * Generates a new API key for the specified physician.
     * @param Physician the physician or physician id
     */
    public static function generate($physician)
    {
        $token = new ApiKey();
        $token->physician_id = $physician instanceof Physician ? $physician->id : $physician;
        $token->token = md5(uniqid(microtime() . rand(), true));
        return $token;
    }
}
