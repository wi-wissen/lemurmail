<?php

namespace App\Console\Commands;

use App\Core\Retention\RetentionPolicy;
use App\Models\Address;
use App\Models\Message;
use App\Support\Clock;
use Illuminate\Console\Command;

class PruneMessages extends Command
{
    protected $signature = 'mail:prune';

    protected $description = 'Delete messages past the retention and drop the plaintext of expired PINs';

    public function handle(): int
    {
        $now = Clock::now();

        $messages = Message::query()
            ->where('received_at', '<', RetentionPolicy::cutoff($now, (int) config('lemurmail.retention_days')))
            ->delete();

        $pins = Address::query()
            ->whereNotNull('pin_expires_at')
            ->where('pin_expires_at', '<=', $now)
            ->whereNotNull('pin_encrypted')
            ->update(['pin_encrypted' => null]);

        $this->info("Deleted $messages message(s), cleared $pins expired PIN(s).");

        return self::SUCCESS;
    }
}
