<?php

namespace App\Http\Controllers\Teacher;

use App\Core\Retention\RetentionPolicy;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Models\Address;
use App\Models\Message;
use App\Services\MailboxPresenter;
use App\Support\Clock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Teachers read the mails of their own addresses, without a PIN and also after the lesson. */
class AddressInboxController extends Controller
{
    use OwnsResources;

    public function inbox(Request $request, Address $address): View
    {
        $this->owned($request, $address);

        return view('teacher.inbox', [
            'address' => $address,
            'group' => $address->group,
            'hasSecret' => $address->secret !== null,
            'days' => min(RetentionPolicy::MAX_DAYS, (int) config('lemurmail.retention_days')),
            'utcOffsetMinutes' => now()->utcOffset(),
        ]);
    }

    public function messages(Request $request, Address $address, MailboxPresenter $presenter): JsonResponse
    {
        $this->owned($request, $address);

        return response()->json($presenter->list($address, Clock::now()));
    }

    public function show(Request $request, Address $address, Message $message, MailboxPresenter $presenter): JsonResponse
    {
        $this->ownedMessage($request, $address, $message);

        return response()->json($presenter->show($message));
    }

    public function destroy(Request $request, Address $address, Message $message): Response
    {
        $this->ownedMessage($request, $address, $message);
        $message->delete();

        return response()->noContent();
    }

    /** Mails of other addresses are reported as missing. */
    private function ownedMessage(Request $request, Address $address, Message $message): void
    {
        $this->owned($request, $address);
        abort_unless((int) $message->address_id === (int) $address->id, 404);
    }
}
