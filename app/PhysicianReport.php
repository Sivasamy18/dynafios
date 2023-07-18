<?php

namespace App;

use App\Models\Files\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhysicianReport extends Model
{

    use SoftDeletes;

    protected $table = 'physician_reports';
    protected $softDelete = true;
    protected $dates = ['deleted_at'];

    public function file()
    {
        return $this->morphOne(File::class, 'fileable');
    }

    public function physician()
    {
        return $this->belongsTo('App\Physician');
    }

    //one-many physician : New function added to update practice_id in table 'physicianreports' by 1254
    public static function updateExistingPhysiciansReportwithPracticeId()
    {
        $physicians = self::distinct('physician_id')->pluck('physician_id')->toArray();
        $error = 0;
        foreach ($physicians as $physician) {
            $practices = Contract::where('physician_id', '=', $physician)->distinct('practice_id')->pluck('practice_id')->toArray();
            if (count($practices) == 1) {
                self::where('physician_id', '=', $physician)->update(['practice_id' => $practices[0]]);

            } else {
                $error++; //check for physician having multiple or zero practices
            }

        }
        return 1;
    }
    //end-one-many physician : New function added to update practice_id in table 'physicianreports' by 1254
}
