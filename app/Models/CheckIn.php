<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    protected $guarded = ['id'];

    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
