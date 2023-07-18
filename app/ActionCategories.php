<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionCategories extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'action_categories';


    public static function getCategoriesByPaymentType($payment_type)
    {
        if ($payment_type == PaymentType::REHAB) {
            return ActionCategories::where('id', '>=', 9)->get();
        } else {
            return ActionCategories::where('id', '<', 9)->get();
        }
    }


}
