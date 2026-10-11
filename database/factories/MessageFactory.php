<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'address_id' => Address::factory(),
            'webhook_id' => (string) Str::uuid(),
            'from_email' => 'noreply@example-dienst.example',
            'subject' => fake()->sentence(4),
            'body_text' => fake()->paragraph(),
            'body_html' => null,
            'received_at' => now()->getTimestamp(),
        ];
    }
}
