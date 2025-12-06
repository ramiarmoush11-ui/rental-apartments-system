<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ApartmentUser extends Pivot
{
    protected $table = 'apartment_user';


    protected $fillable = [
        'apartment_id',
        'user_id',
        'startTerm',
        'endTerm',
        'enStatus',
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
