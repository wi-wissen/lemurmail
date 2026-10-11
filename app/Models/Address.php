<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'group_id', 'local_part', 'pin_hash', 'pin_encrypted', 'pin_expires_at', 'secret'])]
#[Hidden(['pin_hash', 'pin_encrypted', 'secret'])]
class Address extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'pin_expires_at' => 'integer',
            'pin_encrypted' => 'encrypted',
            'secret' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function senderRules(): HasMany
    {
        return $this->hasMany(SenderRule::class);
    }

    public function email(): string
    {
        return $this->local_part.'@'.config('lemurmail.mail_domain');
    }

    public function pinIsValid(int $now): bool
    {
        return $this->pin_hash !== null && $this->pin_expires_at !== null && $this->pin_expires_at > $now;
    }

    /** Fingerprint stored in student sessions; changes whenever the PIN changes. */
    public function pinFingerprint(): string
    {
        return hash('sha256', (string) $this->pin_hash);
    }
}
