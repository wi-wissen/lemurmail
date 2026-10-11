<?php

namespace App\Http\Controllers;

use App\Core\Webhook\InboundPayload;
use App\Core\Webhook\InvalidPayload;
use App\Core\Webhook\SignatureVerifier;
use App\Services\InboundMailProcessor;
use App\Support\Clock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/** Lettermint inbound webhook. Answers 2xx for every mail we do not store, so the sender never learns why. */
class WebhookController extends Controller
{
    public function __invoke(Request $request, InboundMailProcessor $processor): Response
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Lettermint-Signature', '');

        if (! SignatureVerifier::verify(
            $signature,
            $raw,
            (string) config('lemurmail.webhook_secret'),
            Clock::now(),
            (int) config('lemurmail.webhook_tolerance'),
        )) {
            Log::warning('Inbound webhook rejected: invalid signature');
            abort(401);
        }

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            abort(400);
        }

        // Other event types (delivered, bounced, ...) carry no mail for us.
        if (is_string($json['event'] ?? null) && $json['event'] !== 'message.inbound') {
            return response('');
        }

        try {
            $payload = InboundPayload::fromArray($json);
        } catch (InvalidPayload) {
            Log::warning('Inbound webhook rejected: malformed payload');
            abort(400);
        }

        Log::info('Inbound webhook handled', ['outcome' => $processor->process($payload)->value]);

        return response('');
    }
}
