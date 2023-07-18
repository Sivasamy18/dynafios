<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    public function physician()
    {
        return $this->belongsTo("App\Physician");
    }

    public static function generate($physician)
    {
        $access_token = new AccessToken;
        $access_token->physician_id = $physician->id;
        $access_token->key = sha1(bin2hex(openssl_random_pseudo_bytes(64)));
        $access_token->expiration = mysql_date("+30 days");
        return $access_token;
    }
}