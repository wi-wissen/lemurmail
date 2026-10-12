<x-app-layout>
    @include('admin._nav')

    <x-card class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ __('admin.freemail.title') }}</h1>
            <p class="mt-2 text-[15px] text-ink-soft">{{ __('admin.freemail.hint') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.freemail.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[12rem] flex-1">
                <x-input-label for="freemail_domain" :value="__('admin.domains.domain')" />
                <x-text-input id="freemail_domain" name="domain" type="text" required placeholder="{{ __('admin.domains.domain_placeholder') }}" class="mt-2" />
            </div>
            <x-primary-button>{{ __('admin.domains.add') }}</x-primary-button>
        </form>

        @if ($blockedDomains->isEmpty())
            <p class="text-base text-ink-soft">{{ __('admin.freemail.none') }}</p>
        @else
            <ul class="divide-y divide-line rounded-lg border border-line">
                @foreach ($blockedDomains as $blockedDomain)
                    <li class="flex items-center justify-between gap-4 px-4 py-2.5">
                        <span class="min-w-0 break-all font-mono text-[15px] text-ink">{{ $blockedDomain->domain }}</span>
                        <form method="POST" action="{{ route('admin.freemail.destroy', $blockedDomain) }}" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger"
                                aria-label="{{ __('admin.domains.remove_entry', ['entry' => $blockedDomain->domain]) }}">{{ __('admin.domains.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>
