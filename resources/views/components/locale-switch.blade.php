<span class="inline-flex items-center gap-2" aria-label="{{ __('common.language') }}">
    @foreach (config('lemurmail.locales') as $locale)
        @if ($locale === app()->getLocale())
            <span class="font-bold text-ink" aria-current="true">{{ strtoupper($locale) }}</span>
        @else
            <a href="{{ route('locale', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}" class="underline">{{ strtoupper($locale) }}</a>
        @endif
    @endforeach
</span>
