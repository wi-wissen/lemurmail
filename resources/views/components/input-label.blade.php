@props(['value'])
<label {{ $attributes->merge(['class' => 'block text-base font-bold text-ink']) }}>{{ $value ?? $slot }}</label>
