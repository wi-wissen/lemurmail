<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="min-h-screen bg-beamer-bg text-beamer-text">
        {{ $slot }}
    </body>
</html>
