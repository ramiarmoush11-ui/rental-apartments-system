<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
     protected $guarded = [];
     
    public Function users(){
        return $this->BelongsToMany(User::class);
    }
    

     public function users_fav()
    {
        return $this->belongsToMany(User::class, 'favourites');
    }
}
