<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Freemail and disposable-mail providers that teachers may not register with. */
return new class extends Migration
{
    /** @var list<string> */
    private const DOMAINS = [
        'gmail.com', 'gmail.de', 'googlemail.com',
        'outlook.com', 'outlook.de', 'outlook.fr', 'hotmail.com', 'hotmail.de', 'hotmail.fr', 'hotmail.co.uk',
        'live.com', 'live.de', 'msn.com',
        'yahoo.com', 'yahoo.de', 'yahoo.fr', 'yahoo.co.uk',
        'web.de', 'gmx.de', 'gmx.net', 'gmx.at', 'gmx.ch', 'gmx.com',
        't-online.de', 'freenet.de', '1und1.de', 'arcor.de', 'online.de',
        'icloud.com', 'me.com', 'proton.me', 'protonmail.com',
        'aol.com', 'mail.com', 'posteo.de', 'mailbox.org', 'laposte.net',
        'yandex.com', 'mail.ru', 'zoho.com', 'tutanota.com', 'tuta.io',
        'guerrillamail.com', 'guerrillamail.de', 'mailinator.com', '10minutemail.com',
        'tempmail.com', 'temp-mail.org', 'trashmail.com', 'yopmail.com', 'sharklasers.com',
        'maildrop.cc', 'wegwerfmail.de', 'dispostable.com',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('blocked_domains')->insertOrIgnore(array_map(
            fn (string $domain) => ['domain' => $domain, 'created_at' => $now, 'updated_at' => $now],
            self::DOMAINS,
        ));
    }

    public function down(): void
    {
        DB::table('blocked_domains')->whereIn('domain', self::DOMAINS)->delete();
    }
};
