<footer class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-line px-6 py-5 text-[15px] text-ink-soft sm:px-10">
    <span>{{ __('common.receive_only') }}</span>
    @if (config('lemurmail.imprint_url') || config('lemurmail.privacy_url'))
        <nav class="ml-auto flex gap-6" aria-label="{{ __('common.legal') }}">
            @if (config('lemurmail.imprint_url'))
                <a href="{{ config('lemurmail.imprint_url') }}" class="text-ink-soft underline" rel="noopener">{{ __('common.imprint') }}</a>
            @endif
            @if (config('lemurmail.privacy_url'))
                <a href="{{ config('lemurmail.privacy_url') }}" class="text-ink-soft underline" rel="noopener">{{ __('common.privacy') }}</a>
            @endif
        </nav>
    @endif
</footer>
