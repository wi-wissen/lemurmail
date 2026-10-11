<?php

namespace App\Console\Commands;

use App\Core\Webhook\SignatureVerifier;
use App\Models\Message;
use App\Support\Clock;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Delivers a signed fake inbound mail to this app in-process, exactly as Lettermint would. */
class SimulateMail extends Command
{
    protected $signature = 'mail:simulate
        {recipient : Full address, e.g. klasse7a-03@tmp-mail.example.org}
        {--from=noreply@example-dienst.example : Sender address (its domain counts as authenticated)}
        {--subject=Test mail : Subject line}
        {--text=Hallo, dies ist eine simulierte Testmail. : Plain-text body}
        {--html= : HTML body}';

    protected $description = 'Simulate an inbound mail through the signed webhook';

    public function handle(Kernel $kernel): int
    {
        $secret = (string) config('lemurmail.webhook_secret');
        if ($secret === '') {
            $this->error('No webhook secret configured (LETTERMINT_WEBHOOK_SECRET).');

            return self::FAILURE;
        }

        $recipient = (string) $this->argument('recipient');
        $from = (string) $this->option('from');
        $domain = Str::after($from, '@');
        $now = Clock::now();
        $webhookId = (string) Str::uuid();

        $raw = json_encode([
            'id' => $webhookId,
            'event' => 'message.inbound',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z', $now),
            'data' => [
                'message_id' => (string) Str::uuid(),
                'route' => 'catch-all',
                'recipient' => $recipient,
                'envelope' => ['mail_from' => 'bounce@mail.'.$domain, 'remote_ip' => '127.0.0.1'],
                'from' => ['email' => $from],
                'to' => [['email' => $recipient]],
                'subject' => (string) $this->option('subject'),
                'date' => gmdate('Y-m-d\TH:i:s\Z', $now),
                'body' => [
                    'text' => (string) $this->option('text'),
                    'html' => $this->option('html') ?: null,
                ],
                'attachments' => [],
                'authentication_results' => [
                    'spf' => ['result' => 'pass', 'domain' => 'mail.'.$domain],
                    'dkim' => ['result' => 'pass', 'domain' => $domain, 'selector' => 'simulation'],
                    'dmarc' => ['result' => 'pass', 'domain' => $domain, 'policy' => 'none'],
                ],
                'is_spam' => false,
            ],
        ], JSON_THROW_ON_ERROR);

        $request = Request::create('/webhooks/lettermint', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_LETTERMINT_SIGNATURE' => SignatureVerifier::sign($raw, $secret, $now),
        ], $raw);

        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $this->line("HTTP $status");

        $stored = Message::where('webhook_id', $webhookId)->exists();
        $this->line($stored ? 'Message stored.' : 'No message stored (unknown address, sender not allowed or not authenticated).');

        return $status >= 200 && $status < 300 ? self::SUCCESS : self::FAILURE;
    }
}
