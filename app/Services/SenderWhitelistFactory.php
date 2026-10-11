<?php

namespace App\Services;

use App\Core\Mail\SenderWhitelist;
use App\Models\Address;
use App\Models\SenderRule;

/** Builds the sender whitelist that applies to one address: the teacher's default rules plus the address's own. */
class SenderWhitelistFactory
{
    public function forAddress(Address $address): SenderWhitelist
    {
        $patterns = SenderRule::query()
            ->where('user_id', $address->user_id)
            ->where(fn ($query) => $query->whereNull('address_id')->orWhere('address_id', $address->id))
            ->pluck('pattern')
            ->all();

        return new SenderWhitelist($patterns);
    }
}
