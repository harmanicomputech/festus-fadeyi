<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Pledge extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount_kobo' => 'integer'];
    }

    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public static function newPayToken(): string
    {
        return Str::random(40);
    }

    public function payUrl(): string
    {
        return route('donate.show', ['pledge' => $this->pay_token]);
    }
}
