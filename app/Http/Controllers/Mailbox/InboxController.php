<?php

namespace App\Http\Controllers\Mailbox;

use App\Core\Retention\RetentionPolicy;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\MailboxPresenter;
use App\Support\Clock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $address = $request->attributes->get('mailbox_address');
        $now = Clock::now();

        return view('mailbox.inbox', [
            'address' => $address,
            'now' => $now,
            'validUntil' => date('H:i', $address->pin_expires_at),
            'minutesLeft' => (int) ceil(max(0, $address->pin_expires_at - $now) / 60),
            'hasSecret' => $address->secret !== null,
            'days' => min(RetentionPolicy::MAX_DAYS, (int) config('lemurmail.retention_days')),
            'utcOffsetMinutes' => now()->utcOffset(),
        ]);
    }

    public function messages(Request $request, MailboxPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->list($request->attributes->get('mailbox_address'), Clock::now()));
    }

    public function show(Request $request, Message $message, MailboxPresenter $presenter): JsonResponse
    {
        $this->ensureOwnMessage($request, $message);

        return response()->json($presenter->show($message));
    }

    public function destroy(Request $request, Message $message): Response
    {
        $this->ensureOwnMessage($request, $message);
        $message->delete();

        return response()->noContent();
    }

    /** Mails of other addresses are reported as missing. */
    private function ensureOwnMessage(Request $request, Message $message): void
    {
        abort_unless((int) $message->address_id === (int) $request->attributes->get('mailbox_address')->id, 404);
    }
}
