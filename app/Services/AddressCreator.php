<?php

namespace App\Services;

use App\Core\Address\AddressSeries;
use App\Core\Address\LocalPart;
use App\Models\Address;
use App\Models\Group;
use App\Models\SenderRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** Creates teacher addresses. All checks run first; the inserts are all-or-nothing. */
class AddressCreator
{
    /** @return list<Address> */
    public function createSeries(User $teacher, ?Group $group, string $prefix, int $count): array
    {
        try {
            $localParts = AddressSeries::generate($prefix, $count);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['prefix' => __('teacher.errors.invalid_local_part')]);
        }

        return $this->store($teacher, $group, $localParts, 'prefix', 'count');
    }

    public function createSingle(User $teacher, ?Group $group, string $localPart): Address
    {
        $localPart = LocalPart::normalize($localPart);
        if (! LocalPart::isValid($localPart)) {
            throw ValidationException::withMessages(['local_part' => __('teacher.errors.invalid_local_part')]);
        }

        return $this->store($teacher, $group, [$localPart], 'local_part', 'local_part')[0];
    }

    /**
     * @param  list<string>  $localParts
     * @return list<Address>
     */
    private function store(User $teacher, ?Group $group, array $localParts, string $field, string $limitField): array
    {
        if (! SenderRule::where('user_id', $teacher->id)->whereNull('address_id')->exists()) {
            throw ValidationException::withMessages([$field => __('teacher.errors.no_senders')]);
        }

        $taken = Address::whereIn('local_part', $localParts)->pluck('local_part')->all();
        $duplicate = collect($localParts)->first(fn (string $localPart): bool => in_array($localPart, $taken, true));
        if ($duplicate !== null) {
            throw ValidationException::withMessages([$field => __('teacher.errors.local_part_taken', ['local_part' => $duplicate])]);
        }

        $limit = $teacher->addressLimit();
        if ($teacher->addresses()->count() + count($localParts) > $limit) {
            throw ValidationException::withMessages([$limitField => __('teacher.errors.address_limit', ['limit' => $limit])]);
        }

        return DB::transaction(fn (): array => array_map(
            fn (string $localPart): Address => Address::create([
                'user_id' => $teacher->id,
                'group_id' => $group?->id,
                'local_part' => $localPart,
            ]),
            $localParts,
        ));
    }
}
