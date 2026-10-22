<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Donation extends Model
{
    public const METHOD_LABELS = [
        'paystack' => 'Online (Paystack)',
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash / cheque at venue',
        'other' => 'Other',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'is_anonymous' => 'boolean',
            'acknowledge_publicly' => 'boolean',
            'show_amount_publicly' => 'boolean',
            'gateway_payload' => 'array',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
        ];
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeSuccessful(Builder $q): Builder
    {
        return $q->where('status', 'success');
    }

    public static function newReference(): string
    {
        return 'FF85-'.now()->format('ymd').'-'.Str::upper(Str::random(10));
    }

    public function amountNaira(): float
    {
        return $this->amount_kobo / 100;
    }

    public function displayName(): string
    {
        return $this->is_anonymous || ! $this->donor_name ? 'Anonymous' : $this->donor_name;
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? Str::headline($this->method);
    }
}
