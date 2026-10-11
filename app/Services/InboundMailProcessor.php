<?php

namespace App\Services;

use App\Core\Mail\SenderDecision;
use App\Core\Mail\SenderPolicy;
use App\Core\Webhook\InboundPayload;
use App\Models\Address;
use App\Models\Message;

/** Routes one parsed inbound mail to its address and stores it if the sender is allowed. */
class InboundMailProcessor
{
    public function __construct(private readonly SenderWhitelistFactory $whitelists) {}

    public function process(InboundPayload $payload): InboundOutcome
    {
        if (strtolower((string) config('lemurmail.mail_domain')) !== $payload->recipientDomain()) {
            return InboundOutcome::UnknownRecipient;
        }

        $address = Address::where('local_part', $payload->recipientLocalPart())->first();
        if ($address === null) {
            return InboundOutcome::UnknownRecipient;
        }

        $decision = SenderPolicy::decide(
            $this->whitelists->forAddress($address),
            $payload->fromEmail,
            $payload->spf,
            $payload->dkim,
            $payload->dmarc,
        );

        return match ($decision) {
            SenderDecision::Accept => $this->store($address, $payload),
            SenderDecision::NotAllowed => InboundOutcome::NotAllowed,
            SenderDecision::Unauthenticated => InboundOutcome::Unauthenticated,
        };
    }

    /** Race-safe against a redelivered webhook: the unique webhook_id lets only one request create the row. */
    private function store(Address $address, InboundPayload $payload): InboundOutcome
    {
        $message = Message::createOrFirst(
            ['webhook_id' => $payload->webhookId],
            [
                'address_id' => $address->id,
                'from_email' => $payload->fromEmail,
                'subject' => $payload->subject,
                'body_text' => $payload->text,
                'body_html' => $payload->html,
                'received_at' => $payload->receivedAt,
            ],
        );

        return $message->wasRecentlyCreated ? InboundOutcome::Stored : InboundOutcome::Duplicate;
    }
}
