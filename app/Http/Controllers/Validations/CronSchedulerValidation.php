<?php

namespace App\Http\Controllers\Validations;

class CronSchedulerValidation extends Validation
{
    public function validateCreate($data)
    {
        return $this->validate($data, [
            'date' => 'required|between:1,28',
            'day' => 'required|digits:1',
            'type' => 'required|between:1,2'
        ]);
    }
}
