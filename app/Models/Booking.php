<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;//ما عاد بدنا ياها 

class Booking extends Model//pivot -> model 
{
    use HasFactory;

    protected $table = 'bookings';


    protected $fillable = [
        'apartment_id',
        'user_id',
        'startTerm',
        'endTerm',
        'enStatus',
        'priceAtBooking',
    ];
    protected $casts = [
        'startTerm' => 'date',
        'endTerm' => 'date'
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'booking_id');
    }
}
