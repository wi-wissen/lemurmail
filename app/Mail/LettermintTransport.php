<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends system mail through the Lettermint HTTPS API (POST /v1/send) instead of SMTP.
 * Needed where outgoing SMTP ports are blocked. Mail content is never logged.
 */
class LettermintTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $token,
        private readonly string $endpoint = 'https://api.lettermint.co/v1',
        private readonly int $timeout = 10,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $response = Http::withHeaders(['x-lettermint-token' => $this->token])
            ->acceptJson()
            ->timeout($this->timeout)
            ->post(rtrim($this->endpoint, '/').'/send', $this->payload($email, $envelope));

        if ($response->failed()) {
            throw new RuntimeException('Lettermint API rejected the message (HTTP '.$response->status().').');
        }
    }

    /** @return array<string, mixed> */
    private function payload(Email $email, Envelope $envelope): array
    {
        $payload = [
            'from' => $envelope->getSender()->toString(),
            'to' => $this->addresses($email->getTo()),
            'subject' => (string) $email->getSubject(),
        ];

        foreach (['cc' => $email->getCc(), 'bcc' => $email->getBcc(), 'reply_to' => $email->getReplyTo()] as $key => $list) {
            if ($list !== []) {
                $payload[$key] = $this->addresses($list);
            }
        }

        if (($html = $email->getHtmlBody()) !== null) {
            $payload['html'] = (string) $html;
        }
        if (($text = $email->getTextBody()) !== null) {
            $payload['text'] = (string) $text;
        }

        return $payload;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $a->toString(), $addresses);
    }

    public function __toString(): string
    {
        return 'lettermint';
    }
}
