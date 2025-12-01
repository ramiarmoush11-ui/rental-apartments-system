<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];
    protected $casts = [
    // ...
    'data' => 'array', // ✅ هذا يضمن أن Laravel يحول المصفوفة إلى JSON تلقائياً
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
