<?php

namespace App\Services;

use App\Core\Text\Linkify;
use App\Models\Address;
use App\Models\Message;

/** Builds the JSON the inbox polls and shows. Times are Unix seconds. */
final class MailboxPresenter
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /** @return array{now: int, expires_at: int, messages: list<array{id: int, from: string, subject: string, received_at: int}>} */
    public function list(Address $address, int $now): array
    {
        $messages = $address->messages()
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get(['id', 'from_email', 'subject', 'received_at'])
            ->map(fn (Message $message): array => [
                'id' => $message->id,
                'from' => $message->from_email,
                'subject' => $message->subject ?? '',
                'received_at' => $message->received_at,
            ])
            ->values()
            ->all();

        return [
            'now' => $now,
            'expires_at' => $address->pin_expires_at,
            'messages' => $messages,
        ];
    }

    /** The text variant is always included; the sanitized HTML document only when the mail has one. */
    public function show(Message $message): array
    {
        return [
            'id' => $message->id,
            'from' => $message->from_email,
            'subject' => $message->subject ?? '',
            'received_at' => $message->received_at,
            'text_segments' => Linkify::segments($message->body_text ?? ''),
            'html_document' => filled($message->body_html)
                ? $this->sanitizer->document($this->sanitizer->clean($message->body_html))
                : null,
        ];
    }
}
