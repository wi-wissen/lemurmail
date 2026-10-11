<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="flex min-h-screen flex-col">
        <header class="flex flex-wrap items-center justify-between gap-4 px-6 py-6 sm:px-10">
            <a href="{{ url('/') }}" class="text-ink hover:text-ink"><x-wordmark /></a>
            @isset($headerLink){{ $headerLink }}@endisset
        </header>
        <main class="flex flex-1 items-start justify-center px-4 pb-16 pt-6 sm:items-center">
            <div class="w-full {{ $wide ?? false ? 'max-w-5xl' : 'max-w-md' }}">{{ $slot }}</div>
        </main>
        @include('layouts.partials.footer')
    </body>
</html>
