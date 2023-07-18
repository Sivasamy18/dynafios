<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Signature extends Model
{
    protected $table = 'signature';
    protected $fillable = array('physician_id', 'signature_path', 'tokken_id', 'date');

    public function postSignature($physician_id, $signature, $token, $date, $type = "old")
    {
        return $this->submitSignature($physician_id, $signature, $token, $date, $type);
    }

    private function submitSignature($physician_id, $signature, $token, $date, $type)
    {
        $date_check = date('Y-m') . "-01";
        $access_token_Signature = DB::table('signature')
            ->where("physician_id", "=", $physician_id)
            ->where("date", ">=", $date_check)
            ->first();
        //$access_token_Signature_prev = DB::table('signature')->where("physician_id", "=", $access_token->physician_id)->first();


        if ($access_token_Signature) {
//				$this->where('physician_id', $physician_id)
//				->update(array('signature_path' => $signature, 'tokken_id' => $token, 'date' => $date));
        }

        /*Signature::create(
            array('physician_id' => $physician_id, 'signature_path' => $signature, 'tokken_id' => $token, 'date' => $date)
        );*/
        $this->physician_id = $physician_id;
        $this->signature_path = $signature;
        $this->tokken_id = $token;
        $this->date = $date;
        $this->save();
        if ($type === "old") {
            return 1;
        } else {
            return $this->id;
        }
    }
}
