<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $guarded = [];

    // public function users()
    // {
    //     return $this->BelongsToMany(User::class)->withPivot(['enType', 'enStatus', 'rate', 'startTerm', 'endTerm'])
    //         ->withTimestamps();
    // }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function favouriteUsers()
    {
        return $this->belongsToMany(User::class, 'favourites');
    }
}
