@props(['text'])

<button type="button"
        x-data="copyButton"
        data-copy="{{ $text }}"
        data-copied-label="{{ __('mailbox.copied') }}"
        aria-label="{{ __('mailbox.copy_address') }}"
        @click="copy()"
        :title="copied ? copiedLabel : null"
        {{ $attributes->merge(['class' => 'inline-flex h-9 min-h-9 w-9 min-w-9 shrink-0 items-center justify-center rounded-lg border border-line-strong bg-surface text-ink-soft transition hover:border-accent hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent']) }}>
    <svg x-show="!copied" class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect x="9" y="9" width="11" height="11" rx="2"/>
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
    </svg>
    <svg x-show="copied" class="h-5 w-5 text-accent" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20 6 9 17l-5-5"/>
    </svg>
    <span role="status" class="sr-only" x-text="copied ? copiedLabel : ''"></span>
</button>
