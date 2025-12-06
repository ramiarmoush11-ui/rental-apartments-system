<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Symfony\Component\CssSelector\Node\FunctionNode;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'ban_history' => 'array',
];

    protected $guarded = ['enRole',];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function apartments()
    {
        return $this->BelongsToMany(Apartment::class)->withPivot(['enType', 'enStatus', 'rate', 'startTerm', 'endTerm'])
            ->withTimestamps();
    }
    public function profile()
    {
        return  $this->hasOne(Profile::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function favourites()
    {
        return $this->belongsToMany(Apartment::class, 'favourites');
    }
   public function payments()
{
    return $this->hasMany(\App\Models\Payment::class, 'user_id');
}

}
