<?php

namespace App\Core\Webhook;

use App\Core\Mail\AuthResult;

/** Parsed Lettermint `message.inbound` webhook. Pure: no I/O. */
final class InboundPayload
{
    public function __construct(
        public readonly string $webhookId,        // top-level "id"
        public readonly string $recipient,        // data.recipient, trimmed + lower-case
        public readonly ?string $mailFrom,        // data.envelope.mail_from
        public readonly string $fromEmail,        // data.from.email, trimmed + lower-case
        public readonly ?string $subject,         // data.subject
        public readonly ?string $text,            // data.body.text
        public readonly ?string $html,            // data.body.html
        public readonly int $receivedAt,          // data.date (ISO 8601) as Unix seconds
        public readonly ?AuthResult $spf,         // data.authentication_results.spf
        public readonly ?AuthResult $dkim,
        public readonly ?AuthResult $dmarc,
    ) {}

    /**
     * Required: id (non-empty string), event === "message.inbound", data.recipient (non-empty string),
     * data.from.email (non-empty string), data.date (parseable ISO 8601). Everything else optional.
     * Attachments, raw and headers are ignored. Local part of recipient is NOT split here.
     *
     * @param  array<string,mixed>  $json  decoded body
     *
     * @throws InvalidPayload
     */
    public static function fromArray(array $json): self
    {
        if (($json['event'] ?? null) !== 'message.inbound') {
            throw new InvalidPayload('Unexpected event.');
        }

        $id = self::requiredString($json, 'id');
        $data = self::section($json, 'data');
        $recipient = strtolower(trim(self::requiredString($data, 'recipient')));
        if (substr_count($recipient, '@') !== 1) {
            throw new InvalidPayload('Recipient is not an address.');
        }
        $fromEmail = strtolower(trim(self::requiredString(self::section($data, 'from'), 'email')));
        $receivedAt = self::parseTimestamp(self::requiredString($data, 'date'));

        $envelope = self::section($data, 'envelope');
        $body = self::section($data, 'body');
        $auth = self::section($data, 'authentication_results');

        return new self(
            webhookId: $id,
            recipient: $recipient,
            mailFrom: self::optionalString($envelope['mail_from'] ?? null),
            fromEmail: $fromEmail,
            subject: self::optionalString($data['subject'] ?? null),
            text: self::optionalString($body['text'] ?? null),
            html: self::optionalString($body['html'] ?? null),
            receivedAt: $receivedAt,
            spf: self::authResult($auth, 'spf'),
            dkim: self::authResult($auth, 'dkim'),
            dmarc: self::authResult($auth, 'dmarc'),
        );
    }

    /** Local part of the recipient ("klasse7a-01@tmp.example" => "klasse7a-01"). */
    public function recipientLocalPart(): string
    {
        [$localPart] = explode('@', $this->recipient, 2);

        return $localPart;
    }

    /** Domain of the recipient, lower-case. */
    public function recipientDomain(): string
    {
        [, $domain] = explode('@', $this->recipient, 2);

        return $domain;
    }

    /** @param array<string,mixed> $source */
    private static function requiredString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new InvalidPayload("Missing or empty $key.");
        }

        return $value;
    }

    /**
     * @param  array<string,mixed>  $source
     * @return array<string,mixed>
     */
    private static function section(array $source, string $key): array
    {
        $value = $source[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /** @param array<string,mixed> $auth */
    private static function authResult(array $auth, string $key): ?AuthResult
    {
        $entry = self::section($auth, $key);
        if (! is_string($entry['result'] ?? null)) {
            return null;
        }

        return new AuthResult($entry['result'], self::optionalString($entry['domain'] ?? null));
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private static function parseTimestamp(string $date): int
    {
        try {
            return (new \DateTimeImmutable($date))->getTimestamp();
        } catch (\Exception $e) {
            throw new InvalidPayload('Invalid date.', 0, $e);
        }
    }
}
