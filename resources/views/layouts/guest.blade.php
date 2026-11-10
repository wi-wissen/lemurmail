<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="flex min-h-screen flex-col">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line bg-surface px-6 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="text-xl text-ink hover:text-ink"><x-wordmark /></a>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-base">
                @isset($headerLink)
                    {{ $headerLink }}
                @else
                    <a href="{{ route('home') }}"><span aria-hidden="true">←</span> {{ __('common.to_home') }}</a>
                @endisset
                <x-locale-switch />
            </div>
        </header>
        <main class="flex flex-1 items-start justify-center px-4 pb-16 pt-10 sm:pt-14">
            <div class="w-full {{ $wide ? 'max-w-5xl' : 'max-w-[440px]' }}">{{ $slot }}</div>
        </main>
        @include('layouts.partials.footer')
    </body>
</html>
