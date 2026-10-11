<?php

namespace App\Models;

use App\Core\Hour\HourWindow;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'lesson_started_at', 'lesson_ends_at'])]
class Group extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['lesson_started_at' => 'integer', 'lesson_ends_at' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function window(): ?HourWindow
    {
        return $this->lesson_started_at === null
            ? null
            : new HourWindow($this->lesson_started_at, $this->lesson_ends_at);
    }
}
