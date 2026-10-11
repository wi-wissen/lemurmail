<footer class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-line px-6 py-5 text-[15px] text-ink-soft sm:px-10">
    <span>{{ __('common.receive_only') }}</span>
    <a href="{{ config('lemurmail.imprint_url', '#') }}" class="text-ink-soft underline">{{ __('common.imprint') }}</a>
    <a href="{{ config('lemurmail.privacy_url', '#') }}" class="text-ink-soft underline">{{ __('common.privacy') }}</a>
    <span class="ml-auto"><x-locale-switch /></span>
</footer>
