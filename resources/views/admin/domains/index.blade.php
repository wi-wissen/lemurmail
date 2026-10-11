<x-app-layout>
    @include('admin._nav')

    <div class="grid gap-8 lg:grid-cols-2">
        <x-card class="space-y-5">
            <div>
                <h1 class="text-2xl font-bold text-ink">{{ __('admin.domains.organization_title') }}</h1>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('admin.domains.organization_hint') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.domains.organization.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input-label for="organization_domain" :value="__('admin.domains.domain')" />
                    <x-text-input id="organization_domain" name="domain" type="text" required placeholder="{{ __('admin.domains.domain_placeholder') }}" class="mt-2" />
                </div>
                <x-primary-button>{{ __('admin.domains.add') }}</x-primary-button>
            </form>

            @if ($organizationDomains->isEmpty())
                <p class="text-base text-ink-soft">{{ __('admin.domains.none_organization') }}</p>
            @else
                <ul class="divide-y divide-line border-y border-line">
                    @foreach ($organizationDomains as $organizationDomain)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <span class="break-all font-mono text-base text-ink">{{ $organizationDomain->domain }}</span>
                            <form method="POST" action="{{ route('admin.domains.organization.destroy', $organizationDomain) }}">
                                @csrf
                                @method('DELETE')
                                <x-secondary-button>{{ __('admin.domains.remove') }}</x-secondary-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <x-card class="space-y-5">
            <div>
                <h2 class="text-2xl font-bold text-ink">{{ __('admin.domains.freemail_title') }}</h2>
                <p class="mt-2 text-[15px] text-ink-soft">{{ __('admin.domains.freemail_hint') }}</p>
            </div>

            <form method="POST" action="{{ route('admin.domains.freemail.store') }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-0 flex-1">
                    <x-input-label for="freemail_domain" :value="__('admin.domains.domain')" />
                    <x-text-input id="freemail_domain" name="domain" type="text" required placeholder="{{ __('admin.domains.domain_placeholder') }}" class="mt-2" />
                </div>
                <x-primary-button>{{ __('admin.domains.add') }}</x-primary-button>
            </form>

            @if ($blockedDomains->isEmpty())
                <p class="text-base text-ink-soft">{{ __('admin.domains.none_freemail') }}</p>
            @else
                <ul class="grid max-h-96 grid-cols-2 gap-x-4 overflow-y-auto border-y border-line md:grid-cols-3">
                    @foreach ($blockedDomains as $blockedDomain)
                        <li class="flex items-center justify-between gap-2 border-b border-line py-2">
                            <span class="min-w-0 break-all font-mono text-[15px] text-ink">{{ $blockedDomain->domain }}</span>
                            <form method="POST" action="{{ route('admin.domains.freemail.destroy', $blockedDomain) }}" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger" aria-label="{{ __('admin.domains.remove').' '.$blockedDomain->domain }}">{{ __('admin.domains.remove') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
</x-app-layout>
