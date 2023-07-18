<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class OnCallActivity extends Model
{
    protected $table = 'on_call_activities';

    //protected $fillable = ['action_id','name','contract_id'];

    public function action()
    {
        return $this->belongsTo('App\Action');
    }

    public function physician()
    {
        return $this->hasMany('App\Physician');
    }
}

?>
