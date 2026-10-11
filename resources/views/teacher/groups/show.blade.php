@use('App\Core\Pin\PinFormat')
@use('Illuminate\Support\Facades\Route')
<x-app-layout>
    <div class="flex flex-col gap-6">
        <header class="flex flex-wrap items-baseline gap-x-4 gap-y-2">
            <h1 class="text-3xl font-bold leading-tight">{{ $group->name }}</h1>
            <span class="text-lg text-ink-soft">{{ trans_choice('teacher.groups.address_count', $addresses->count(), ['count' => $addresses->count()]) }}</span>
        </header>

        @include('teacher.groups._lesson', ['group' => $group])

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <section class="min-w-0 overflow-hidden rounded-lg border border-line bg-surface lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4">
                    <h2 class="text-xl font-bold">{{ __('teacher.addresses.table_title') }}</h2>
                    <div class="flex flex-wrap gap-4 text-base">
                        <a href="{{ route('groups.pins.csv', $group) }}" class="font-bold text-accent underline">{{ __('teacher.pins.csv') }}</a>
                        <a href="{{ route('groups.print', $group) }}" class="font-bold text-accent underline">{{ __('teacher.pins.print') }}</a>
                    </div>
                </div>

                @if ($addresses->isEmpty())
                    <p class="px-6 py-6 text-ink-soft">{{ __('teacher.addresses.empty') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-base">
                            <thead class="text-left text-[15px] text-ink-soft">
                                <tr>
                                    <th scope="col" class="px-6 py-3 font-bold">{{ __('teacher.addresses.column_address') }}</th>
                                    <th scope="col" class="px-3 py-3 font-bold">{{ __('teacher.addresses.column_pin') }}</th>
                                    <th scope="col" class="px-3 py-3 font-bold">{{ __('teacher.addresses.column_mails') }}</th>
                                    <th scope="col" class="px-6 py-3"><span class="sr-only">{{ __('teacher.addresses.delete') }}</span></th>
                                </tr>
                            </thead>
                            @foreach ($addresses as $address)
                                @php($pin = $address->pin_encrypted !== null && $address->pinIsValid($now) ? PinFormat::display($address->pin_encrypted) : null)
                                <tbody class="border-t border-line align-top">
                                    <tr>
                                        <td class="px-6 py-3 font-mono">{{ $address->local_part }}</td>
                                        <td class="px-3 py-3 font-mono tracking-wider">{{ $pin ?? __('teacher.addresses.pin_none') }}</td>
                                        <td class="px-3 py-3">{{ $address->messages_count }}</td>
                                        <td class="px-6 py-3">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a href="{{ route('addresses.inbox', $address) }}" class="inline-flex min-h-[40px] items-center px-3 text-[15px] font-bold text-accent underline">{{ __('teacher.addresses.inbox') }}</a>
                                                @if (Route::has('addresses.pin.regenerate'))
                                                    <form method="POST" action="{{ route('addresses.pin.regenerate', $address) }}">
                                                        @csrf
                                                        <x-secondary-button class="min-h-[40px] px-3 text-[15px]">{{ __('teacher.addresses.regenerate_pin') }}</x-secondary-button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('addresses.destroy', $address) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="min-h-[40px] px-3 text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('teacher.addresses.delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 pb-4">
                                            <details>
                                                <summary class="min-h-[40px] cursor-pointer py-2 text-[15px] font-bold text-accent">{{ __('teacher.addresses.extra_senders', ['count' => $address->senderRules->count()]) }}</summary>
                                                <div class="mt-2 flex flex-col gap-3">
                                                    <p class="text-[15px] leading-snug text-ink-soft">{{ __('teacher.addresses.extra_senders_hint') }}</p>
                                                    @forelse ($address->senderRules as $rule)
                                                        <div class="flex flex-wrap items-center gap-3 font-mono text-[14px]">
                                                            <span>{{ $rule->pattern }}</span>
                                                            <form method="POST" action="{{ route('senders.destroy', $rule) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" aria-label="{{ __('teacher.senders.remove_label', ['pattern' => $rule->pattern]) }}"
                                                                    class="font-sans text-[14px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('teacher.senders.remove') }}</button>
                                                            </form>
                                                        </div>
                                                    @empty
                                                        <p class="text-[15px] text-ink-soft">{{ __('teacher.addresses.no_extra_senders') }}</p>
                                                    @endforelse
                                                    <form method="POST" action="{{ route('addresses.senders.store', $address) }}" class="flex flex-wrap gap-2">
                                                        @csrf
                                                        <x-text-input name="pattern" type="text" class="min-w-0 flex-1 font-mono text-[15px]"
                                                            aria-label="{{ __('teacher.senders.add_label') }}" placeholder="{{ __('teacher.senders.placeholder') }}" required />
                                                        <x-secondary-button class="min-h-[40px] px-4 text-[15px]">{{ __('teacher.senders.add') }}</x-secondary-button>
                                                    </form>
                                                    <x-input-error :messages="$errors->getBag('address_sender')->get('pattern')" />
                                                </div>
                                            </details>
                                        </td>
                                    </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @endif
            </section>

            <aside class="flex flex-col gap-6">
                @include('teacher.senders._list', ['rules' => $defaultRules])

                <section class="flex flex-col gap-4 rounded-lg border border-line bg-surface p-6">
                    <h2 class="text-xl font-bold">{{ __('teacher.addresses.create_title') }}</h2>
                    <form method="POST" action="{{ route('groups.addresses.store', $group) }}" class="flex flex-col gap-4">
                        @csrf
                        <div class="flex gap-3">
                            <div class="flex min-w-0 flex-[2] flex-col gap-1.5">
                                <x-input-label for="prefix">{{ __('teacher.addresses.prefix') }}</x-input-label>
                                <x-text-input id="prefix" name="prefix" type="text" class="font-mono text-[15px]" value="{{ old('prefix') }}" required />
                            </div>
                            <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                                <x-input-label for="count">{{ __('teacher.addresses.count') }}</x-input-label>
                                <x-text-input id="count" name="count" type="number" min="1" max="1000" class="font-mono text-[15px]" value="{{ old('count', 30) }}" required />
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('prefix')" />
                        <x-input-error :messages="$errors->get('count')" />
                        <x-secondary-button class="min-h-[44px] self-start text-base text-accent">{{ __('teacher.addresses.create_series') }}</x-secondary-button>
                    </form>

                    <form method="POST" action="{{ route('addresses.store') }}" class="flex flex-col gap-2 border-t border-line pt-4">
                        @csrf
                        <input type="hidden" name="group_id" value="{{ $group->id }}">
                        <x-input-label for="local_part">{{ __('teacher.addresses.single_label') }}</x-input-label>
                        <div class="flex gap-2">
                            <x-text-input id="local_part" name="local_part" type="text" class="min-w-0 flex-1 font-mono text-[15px]" value="{{ old('local_part') }}" required />
                            <x-secondary-button class="min-h-[44px] px-4 text-base">{{ __('teacher.addresses.create_single') }}</x-secondary-button>
                        </div>
                        <x-input-error :messages="$errors->get('local_part')" />
                    </form>
                </section>

                <section class="flex flex-col gap-4 rounded-lg border border-line bg-surface p-6">
                    <form method="POST" action="{{ route('groups.update', $group) }}" class="flex flex-col gap-3">
                        @csrf
                        @method('PUT')
                        <x-input-label for="group-name">{{ __('teacher.groups.name') }}</x-input-label>
                        <div class="flex gap-2">
                            <x-text-input id="group-name" name="name" type="text" class="min-w-0 flex-1" value="{{ old('name', $group->name) }}" required maxlength="100" />
                            <x-secondary-button class="min-h-[44px] px-4 text-base">{{ __('teacher.groups.rename') }}</x-secondary-button>
                        </div>
                        <x-input-error :messages="$errors->get('name')" />
                    </form>

                    <form method="POST" action="{{ route('groups.destroy', $group) }}" class="flex flex-col gap-2 border-t border-line pt-4">
                        @csrf
                        @method('DELETE')
                        <p class="text-[15px] leading-snug text-ink-soft">{{ __('teacher.groups.delete_hint') }}</p>
                        <button type="submit" class="min-h-[44px] self-start text-base font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('teacher.groups.delete') }}</button>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
