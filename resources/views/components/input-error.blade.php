@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-[15px] font-bold text-danger space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
