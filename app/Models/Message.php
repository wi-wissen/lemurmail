<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['address_id', 'webhook_id', 'from_email', 'subject', 'body_text', 'body_html', 'received_at'])]
class Message extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['received_at' => 'integer'];
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
