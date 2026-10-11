<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="flex min-h-screen flex-col">
        <header class="border-b border-line bg-surface">
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 sm:px-8">
                <div class="flex flex-wrap items-center gap-x-8 gap-y-2">
                    <a href="{{ route('groups.index') }}" class="text-ink hover:text-ink"><x-wordmark class="text-xl" /></a>
                    @auth
                        <nav class="flex flex-wrap gap-x-6 gap-y-1 text-[17px]" aria-label="{{ __('common.main_navigation') }}">
                            <x-nav-item :href="route('groups.index')" :active="request()->routeIs('groups.*')">{{ __('common.nav_groups') }}</x-nav-item>
                            <x-nav-item :href="route('senders.index')" :active="request()->routeIs('senders.*')">{{ __('common.nav_senders') }}</x-nav-item>
                            <x-nav-item :href="route('profile.edit')" :active="request()->routeIs('profile.*')">{{ __('common.nav_account') }}</x-nav-item>
                            @if (auth()->user()->isSuperAdmin())
                                <x-nav-item :href="route('admin.users.index')" :active="request()->routeIs('admin.*')">{{ __('common.nav_admin') }}</x-nav-item>
                            @endif
                        </nav>
                    @endauth
                </div>
                @auth
                    <div class="flex items-center gap-4 text-base text-ink-soft">
                        <span>{{ auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button type="submit" class="text-accent underline hover:text-accent-hover">{{ __('common.logout') }}</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>
        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-8">
            <x-flash />
            {{ $slot }}
        </main>
        @include('layouts.partials.footer')
    </body>
</html>
