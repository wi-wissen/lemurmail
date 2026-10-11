<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('layouts.partials.head')</head>
    <body class="min-h-screen bg-surface text-ink print:bg-white">
        <main class="mx-auto max-w-3xl px-6 py-8 print:max-w-none print:p-0">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 print:hidden">
                <a href="{{ route('groups.show', $group) }}" class="text-base font-bold text-accent underline">{{ __('common.back') }}</a>
                <button type="button" onclick="window.print()"
                    class="min-h-[44px] rounded-lg bg-accent px-5 text-base font-bold text-white hover:bg-accent-hover">{{ __('teacher.pins.print') }}</button>
            </div>

            <h1 class="mb-6 text-2xl font-bold print:text-xl">{{ __('teacher.pins.print_title', ['group' => $group->name]) }}</h1>

            @if ($rows->isEmpty())
                <p class="text-lg text-ink-soft">{{ __('teacher.pins.empty') }}</p>
            @else
                <table class="w-full border-collapse text-lg print:text-base">
                    <thead>
                        <tr class="border-b-2 border-ink text-left">
                            <th scope="col" class="py-2 pr-4 font-bold">{{ __('teacher.addresses.column_address') }}</th>
                            <th scope="col" class="py-2 pr-4 font-bold">{{ __('teacher.addresses.column_pin') }}</th>
                            <th scope="col" class="py-2 font-bold">{{ __('teacher.pins.valid_until') }}</th>
                        </tr>
                    </thead>
                    <tbody class="font-mono">
                        @foreach ($rows as $row)
                            <tr class="break-inside-avoid border-b border-line print:border-gray-400">
                                <td class="py-2 pr-4">{{ $row['address'] }}</td>
                                <td class="py-2 pr-4 text-xl font-bold tracking-wider print:text-lg">{{ $row['pin'] }}</td>
                                <td class="py-2 font-sans">{{ $row['valid_until'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </main>
    </body>
</html>
