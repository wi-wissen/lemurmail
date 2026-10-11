<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'group_id' => null,
            'local_part' => 'a'.fake()->unique()->numerify('######'),
        ];
    }

    /** Address with a live PIN. $pin is the plaintext; $expiresAt is a Unix timestamp. */
    public function withPin(string $pin = 'K7M4QX9R', ?int $expiresAt = null): static
    {
        return $this->state(fn () => [
            'pin_hash' => Hash::make($pin),
            'pin_encrypted' => $pin,
            'pin_expires_at' => $expiresAt ?? now()->getTimestamp() + 3600,
        ]);
    }

    public function inGroup(Group $group): static
    {
        return $this->state(fn () => ['group_id' => $group->id, 'user_id' => $group->user_id]);
    }
}
