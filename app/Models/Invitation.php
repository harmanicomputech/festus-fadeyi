<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'invitation_sent_at' => 'datetime',
            'email_opt_out' => 'boolean',
        ];
    }

    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class);
    }

    /** A short, unambiguous, non-sequential code (~50 bits of entropy). */
    public static function newCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));

        return $code === '' ? null : static::query()->where('code', $code)->first();
    }

    public function link(): string
    {
        return route('invite.show', $this->code);
    }

    public function salutation(): string
    {
        return trim(($this->title ? $this->title.' ' : '').$this->name);
    }

    public static function normaliseEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
