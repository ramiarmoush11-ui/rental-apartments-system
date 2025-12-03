<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->BelongsToMany(User::class)->withPivot(['enType', 'enStatus', 'rate', 'startTerm', 'endTerm'])
            ->withTimestamps();
    }


    public function users_fav()
    {
        return $this->belongsToMany(User::class, 'favourites');
    }
}
