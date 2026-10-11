@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full rounded-lg border-[1.5px] border-line-strong bg-surface px-3.5 py-3 text-[17px] text-ink focus:border-accent focus:ring-accent']) }}>
