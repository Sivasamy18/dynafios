<?php

namespace App;

use Log;
use Illuminate\Database\Eloquent\Model;

class InvoiceNote extends Model
{
    protected $table = 'invoice_notes';

    const HOSPITAL = 1;
    const PRACTICE = 2;
    const CONTRACT = 3;
    const PHYSICIAN = 4;

    const HOSPITALCOUNT = 1;
    const PRACTICECOUNT = 1;
    const PHYSICIANCOUNT = 4;
    const CONTRACTCOUNT = 2;

    public static function getInvoiceNotes($noteFor, $noteType, $hospital_id, $practice_id = 0)
    {
        $notes = self::where('note_type', '=', $noteType)
            ->where('note_for', '=', $noteFor)
            ->where('hospital_id', '=', $hospital_id);
        if ($practice_id != 0) {
            $notes = $notes->where('practice_id', '=', $practice_id);
        }

        $notes = $notes->where('is_active', '=', true)->pluck('note', 'note_index');

        return $notes;
    }

    // Below function is used for adding hospital ids for respective hospital,practice,contract,physician for old data.
    // Added by Akash.
    public static function updateExistingInvoiceNotesWithHospitalId()
    {
        ini_set('max_execution_time', 6000);
        // get the data from invoice_notes table and then derive its hospital_id and update the same in the table.
        $hospital_invoice_notes = self::where('note_type', '=', self::HOSPITAL)->pluck('note_for')->toArray();
        $practice_invoice_notes = self::where('note_type', '=', self::PRACTICE)->pluck('note_for')->toArray();
        $contract_invoice_notes = self::where('note_type', '=', self::CONTRACT)->pluck('note_for')->toArray();
        $physician_invoice_notes = self::where('note_type', '=', self::PHYSICIAN)->pluck('note_for')->toArray();

        foreach ($hospital_invoice_notes as $hospital_id) {
            self::where('note_type', '=', self::HOSPITAL)->where('note_for', '=', $hospital_id)->update(['hospital_id' => $hospital_id]);
        }

        foreach ($practice_invoice_notes as $practice_id) {
            $practice = Practice::where('id', '=', $practice_id)->first();
            if ($practice != null) {
                self::where('note_type', '=', self::PRACTICE)->where('note_for', '=', $practice_id)->update(['hospital_id' => $practice->hospital_id]);
            }
        }

        foreach ($contract_invoice_notes as $contract_id) {
            $contract = Contract::where('id', '=', $contract_id)->first();
            if ($contract !== null) {
                if ($contract['practice_id'] != 0) {
                    $practice = Practice::where('id', '=', $contract->practice_id)->first();
                    if ($practice != null) {
                        self::where('note_type', '=', self::CONTRACT)->where('note_for', '=', $contract_id)->update(['hospital_id' => $practice->hospital_id]);
                    }
                } else {
                    $agreement = Agreement::where('id', '=', $contract->agreement_id)->first();
                    if ($agreement != null) {
                        self::where('note_type', '=', self::CONTRACT)->where('note_for', '=', $contract_id)->update(['hospital_id' => $agreement->hospital_id]);
                    }
                }
            }
        }

        foreach ($physician_invoice_notes as $physician_id) {

            $physician = Physician::where('id', '=', $physician_id)->first();
            if ($physician) {
                $practice = Practice::where('id', '=', $physician->practice_id)->first();
                if ($practice != null) {
                    self::where('note_type', '=', self::PHYSICIAN)->where('note_for', '=', $physician_id)->update(['hospital_id' => $practice->hospital_id]);
                }
            }
        }

        return 1;
    }
}
