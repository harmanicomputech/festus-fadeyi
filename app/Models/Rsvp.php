<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class Rsvp extends Model
{
    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_WAITLISTED = 'waitlisted';

    public const STATUS_MAYBE = 'maybe';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_LABELS = [
        self::STATUS_CONFIRMED => 'Accepted',
        self::STATUS_WAITLISTED => 'Waitlisted',
        self::STATUS_MAYBE => 'Maybe',
        self::STATUS_DECLINED => 'Declined',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['manage_token', 'manage_token_hash'];

    protected function casts(): array
    {
        return [
            'party_size' => 'integer',
            'waitlisted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'email_opt_out' => 'boolean',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function pledge(): HasOne
    {
        return $this->hasOne(Pledge::class)->latestOfMany();
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    /** Issue a fresh manage token; returns the plain token. */
    public function issueManageToken(): string
    {
        $token = Str::random(48);
        $this->manage_token_hash = hash('sha256', $token);
        $this->manage_token = Crypt::encryptString($token);

        return $token;
    }

    public static function findByManageToken(?string $token): ?self
    {
        if (! is_string($token) || strlen($token) < 40) {
            return null;
        }

        return static::query()->where('manage_token_hash', hash('sha256', $token))->first();
    }

    public function plainManageToken(): string
    {
        return Crypt::decryptString($this->manage_token);
    }

    public function manageUrl(): string
    {
        return route('rsvp.manage', $this->plainManageToken());
    }

    public static function newQrToken(): string
    {
        return Str::random(40);
    }

    public function qrUrl(): string
    {
        return route('qr.show', $this->qr_token);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? Str::headline($this->status);
    }

    public function salutation(): string
    {
        $title = $this->title === 'Other' ? '' : $this->title.' ';

        return trim($title.$this->full_name);
    }
}
