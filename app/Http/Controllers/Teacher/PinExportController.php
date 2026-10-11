<?php

namespace App\Http\Controllers\Teacher;

use App\Core\Pin\PinFormat;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Models\Address;
use App\Models\Group;
use App\Support\Clock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Hands out the live PINs of a group (only addresses whose PIN is valid right now). */
class PinExportController extends Controller
{
    use OwnsResources;

    public function csv(Request $request, Group $group): StreamedResponse
    {
        $this->owned($request, $group);
        $addresses = $this->livePins($group);

        return response()->streamDownload(function () use ($addresses): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['address', 'pin', 'valid_until']);
            foreach ($addresses as $address) {
                fputcsv($out, [
                    $address->email(),
                    $address->pin_encrypted,
                    Carbon::createFromTimestamp($address->pin_expires_at)->toIso8601String(),
                ]);
            }
            fclose($out);
        }, Str::slug($group->name).'-pins.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Request $request, Group $group): View
    {
        $this->owned($request, $group);

        return view('teacher.groups.print', [
            'group' => $group,
            'rows' => $this->livePins($group)->map(fn (Address $address): array => [
                'address' => $address->email(),
                'pin' => PinFormat::display($address->pin_encrypted),
                'valid_until' => Carbon::createFromTimestamp($address->pin_expires_at)->format(__('teacher.pins.datetime_format')),
            ]),
        ]);
    }

    /** @return Collection<int, Address> */
    private function livePins(Group $group): Collection
    {
        $now = Clock::now();

        return $group->addresses()->orderBy('local_part')->get()
            ->filter(fn (Address $address): bool => $address->pin_encrypted !== null && $address->pinIsValid($now))
            ->values();
    }
}
