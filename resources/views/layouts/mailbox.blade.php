<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="flex min-h-screen flex-col">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line bg-surface px-6 py-4 sm:px-8">
            <div class="flex flex-wrap items-center gap-5">
                <span class="text-xl"><x-wordmark /></span>
                @isset($meta){{ $meta }}@endisset
            </div>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <x-locale-switch />
                @isset($actions)<div class="flex flex-wrap items-center gap-4">{{ $actions }}</div>@endisset
            </div>
        </header>
        <main class="flex flex-1 flex-col">
            {{ $slot }}
        </main>
        @include('layouts.partials.footer')
    </body>
</html>
