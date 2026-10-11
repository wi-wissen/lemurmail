<x-app-layout>
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
        <section class="min-w-0 flex-1">
            <h1 class="mb-6 text-3xl font-bold leading-tight">{{ __('teacher.groups.title') }}</h1>

            @if ($groups->isEmpty())
                <x-card><p class="text-lg text-ink-soft">{{ __('teacher.groups.empty') }}</p></x-card>
            @else
                <ul class="divide-y divide-line overflow-hidden rounded-lg border border-line bg-surface">
                    @foreach ($groups as $group)
                        <li>
                            <a href="{{ route('groups.show', $group) }}" class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 px-6 py-4 hover:bg-paper">
                                <span class="text-xl font-bold">{{ $group->name }}</span>
                                <span class="text-base text-ink-soft">{{ trans_choice('teacher.groups.address_count', $group->addresses_count, ['count' => $group->addresses_count]) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside class="w-full lg:w-96">
            <x-card class="flex flex-col gap-4 p-6">
                <h2 class="text-xl font-bold">{{ __('teacher.groups.create') }}</h2>
                <form method="POST" action="{{ route('groups.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <x-input-label for="name">{{ __('teacher.groups.name') }}</x-input-label>
                        <x-text-input id="name" name="name" type="text" class="mt-1" value="{{ old('name') }}" required maxlength="100" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <x-primary-button class="min-h-[44px] text-base">{{ __('teacher.groups.create') }}</x-primary-button>
                </form>
            </x-card>
        </aside>
    </div>
</x-app-layout>
