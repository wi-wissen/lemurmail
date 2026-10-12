<x-app-layout>
    @include('admin._nav')

    <x-card class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-ink">{{ __('admin.domains.organization_title') }}</h1>
            <p class="mt-2 text-[15px] text-ink-soft">{{ __('admin.domains.explanation') }}</p>
        </div>

        <form method="POST" action="{{ route('admin.domains.organization.store') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="min-w-[12rem] flex-1">
                <x-input-label for="organization_domain" :value="__('admin.domains.domain')" />
                <x-text-input id="organization_domain" name="domain" type="text" required placeholder="{{ __('admin.domains.domain_placeholder') }}" class="mt-2" />
            </div>
            <div class="min-w-[12rem] flex-1">
                <x-input-label for="organization_pattern" :value="__('admin.domains.pattern')" />
                <x-text-input id="organization_pattern" name="pattern" type="text" placeholder="{{ __('admin.domains.pattern_placeholder') }}" class="mt-2 font-mono" />
            </div>
            <x-primary-button>{{ __('admin.domains.add') }}</x-primary-button>
        </form>

        @if ($organizationDomains->isEmpty())
            <p class="text-base text-ink-soft">{{ __('admin.domains.none_organization') }}</p>
        @else
            <ul class="divide-y divide-line border-y border-line">
                @foreach ($organizationDomains as $organizationDomain)
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3">
                        <div class="flex min-w-0 flex-wrap items-center gap-3">
                            <span class="break-all font-bold text-ink">{{ $organizationDomain->domain }}</span>
                            @if ($organizationDomain->local_part_pattern === null)
                                <span class="text-[15px] text-ink-soft">{{ __('admin.domains.pattern_all') }}</span>
                            @else
                                <span class="break-all rounded border border-line bg-paper px-2 py-0.5 font-mono text-[15px] text-ink">{{ $organizationDomain->local_part_pattern }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.domains.organization.destroy', $organizationDomain) }}" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger"
                                aria-label="{{ __('admin.domains.remove_entry', ['entry' => $organizationDomain->domain.' '.($organizationDomain->local_part_pattern ?? __('admin.domains.pattern_all'))]) }}">{{ __('admin.domains.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</x-app-layout>
