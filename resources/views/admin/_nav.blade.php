<div class="mb-8 space-y-4">
    <nav class="flex flex-wrap gap-x-8 gap-y-2 text-[17px]" aria-label="{{ __('common.main_navigation') }}">
        <x-nav-item :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">{{ __('admin.nav.users') }}</x-nav-item>
        <x-nav-item :href="route('admin.domains.index')" :active="request()->routeIs('admin.domains.*')">{{ __('admin.nav.domains') }}</x-nav-item>
    </nav>

    @if ($errors->any())
        <x-alert type="error">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif
</div>
