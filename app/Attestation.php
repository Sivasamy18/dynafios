<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\State;
use Auth;
use Request;
use Redirect;
use Lang;
use View;
use Illuminate\Support\HtmlString;

class Attestation extends Model
{
    protected $table = "attestations";

    public static function getAttestation()
    {
        $states = options(State::orderBy('name')->get(), 'id', 'name');
        $default_state_key = key($states);
        $data['states'] = $states;

        $attestations = options(self::where('is_active', '=', true)->get(), 'id', 'name');
        $default_attestation_key = key($attestations);
        $data['attestations'] = $attestations;

        $attestation_types = options(DB::table('attestation_types')->where('is_active', '=', true)->get(), 'id', 'name');
        $default_attestation_type_key = key($attestation_types);
        $data['attestation_types'] = $attestation_types;

        $state = Request::input('state', null);
        $attestation = Request::input('attestation', null);
        $attestation_type = Request::input('attestation_type', null);

        if ($state != null && $attestation != null && $attestation != null) {
            $state_id = $state;
            $attestation_id = $attestation;
            $attestation_type_id = $attestation_type;
        } else {
            $state_id = $default_state_key;
            $attestation_id = $default_attestation_key;
            $attestation_type = $default_attestation_type_key;
        }

        $attestation_questions = AttestationQuestion::getAttestationQuestions($state_id, $attestation_id, $attestation_type);
        $data['attestation_questions'] = $attestation_questions;

        $data['pagination'] = "";

        $data['table'] = View::make('attestations/attestation_table')->with($data)->render();

        return $data;
    }
}
