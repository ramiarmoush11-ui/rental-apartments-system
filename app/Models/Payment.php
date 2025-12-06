<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    //

     protected $fillable = [
        'booking_id',
        'user_id',
        'amount',
        'cardNumber',
    ];

    public function booking()
    {
        return $this->belongsTo(ApartmentUser::class, 'booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
