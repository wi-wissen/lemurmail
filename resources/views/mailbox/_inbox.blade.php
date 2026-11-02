@php
    $inbox = array_merge($inbox, [
        'pollMs' => config('lemurmail.poll_seconds') * 1000,
        'utcOffsetMinutes' => $utcOffsetMinutes,
        'hasSecret' => $hasSecret,
        'strings' => [
            'loading' => __('mailbox.messages_loading'),
            'countNone' => __('mailbox.messages_none'),
            'countOne' => __('mailbox.messages_one', ['days' => $days]),
            'countMany' => __('mailbox.messages_many', ['days' => $days]),
            'loadFailed' => __('mailbox.load_failed'),
            'noSubject' => __('mailbox.no_subject'),
            'mailLoading' => __('mailbox.mail_loading'),
            'deleteConfirm' => __('mailbox.delete_confirm'),
            'reload' => __('mailbox.reload'),
            'reloading' => __('mailbox.reloading'),
            'viewTextOnly' => __('mailbox.view_text_only'),
            'viewFormatted' => __('mailbox.view_formatted'),
                        'secretMask' => '••••••••••',
            'secretShow' => __('mailbox.secret_show'),
            'secretCopy' => __('mailbox.secret_copy'),
            'secretCopied' => __('mailbox.secret_copied'),
            'secretHide' => __('mailbox.secret_hide'),
        ],
    ]);
@endphp

<div x-data="inbox(@js($inbox))" class="flex flex-1 flex-col">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-b border-line bg-surface px-6 py-3">
        <span class="text-base font-bold">{{ __('mailbox.secret_title') }}</span>
        @if ($hasSecret)
            <span class="break-all font-mono text-lg tracking-[2px]" x-text="secretText">••••••••••</span>
            <button type="button" @click="toggleSecret()" class="min-h-[40px] px-2 text-base font-bold text-accent underline"
                    x-text="secretVisible ? strings.secretHide : strings.secretShow">{{ __('mailbox.secret_show') }}</button>
            <button type="button" @click="copySecret()" class="min-h-[40px] px-2 text-base font-bold text-accent underline"
                    x-text="secretCopied ? strings.secretCopied : strings.secretCopy">{{ __('mailbox.secret_copy') }}</button>
            <a href="{{ $secretEditUrl }}" class="inline-flex min-h-[40px] items-center px-2 text-base font-bold text-accent underline">{{ __('mailbox.secret_change') }}</a>
        @else
            <a href="{{ $secretEditUrl }}" class="inline-flex min-h-[40px] items-center text-base font-bold text-accent underline">{{ __('mailbox.secret_add') }}</a>
        @endif
        <span class="text-[15px] text-ink-soft">{{ __('mailbox.secret_hint') }}</span>
    </div>

    <div class="flex flex-1 flex-col lg:flex-row">
    <section class="flex flex-col border-b border-line bg-surface lg:w-[360px] lg:shrink-0 lg:border-b-0 lg:border-r"
             aria-label="{{ __('mailbox.inbox_title') }}">
        <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-3">
            <p class="text-[15px] text-ink-soft" x-text="countText">{{ __('mailbox.messages_loading') }}</p>
            <button type="button" @click="reload()" :disabled="refreshing"
                    class="inline-flex min-h-[40px] shrink-0 items-center gap-2 rounded-lg border-[1.5px] border-line-strong bg-surface px-3 text-[15px] font-bold text-ink hover:bg-paper disabled:opacity-60">
                <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" :class="refreshing ? 'animate-spin' : ''"><path d="M13.5 8a5.5 5.5 0 1 1-1.6-3.9M13.5 2.5v3h-3"/></svg>
                <span x-text="refreshing ? strings.reloading : strings.reload">{{ __('mailbox.reload') }}</span>
            </button>
        </div>

        <ul class="divide-y divide-line">
            <template x-for="message in messages" :key="message.id">
                <li>
                    <button type="button" @click="select(message.id)"
                            class="flex w-full min-w-0 flex-col gap-1 px-6 py-3.5 text-left hover:bg-paper"
                            :class="message.id === selectedId ? 'bg-accent-soft hover:bg-accent-soft' : ''"
                            :aria-current="message.id === selectedId ? 'true' : null">
                        <span class="flex items-baseline justify-between gap-3 text-[15px]">
                            <span class="truncate font-bold" x-text="message.from"></span>
                            <span class="shrink-0 text-ink-soft" x-text="clock(message.receivedAt)"></span>
                        </span>
                        <span class="break-words text-[17px]" :class="message.subject === '' ? 'italic text-ink-soft' : ''"
                              x-text="message.subject || strings.noSubject"></span>
                    </button>
                </li>
            </template>
        </ul>

        <p x-show="loadError" role="status" class="px-6 py-4 text-[15px] font-bold text-danger" x-text="strings.loadFailed"></p>
    </section>

    <section class="flex min-w-0 flex-1 flex-col gap-6 p-6 sm:p-8">
        <p x-show="selectedId === null" class="text-[17px] text-ink-soft">{{ __('mailbox.select_mail') }}</p>

        <div x-show="selectedId !== null" class="flex min-w-0 flex-col gap-5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 space-y-2">
                    <h2 class="break-words text-[28px] leading-tight" x-text="detail ? (detail.subject || strings.noSubject) : strings.mailLoading"></h2>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-base text-ink-soft" x-show="detail">
                        <span>{{ __('mailbox.from') }}</span>
                        <span class="break-all font-bold text-ink" x-text="detail?.from"></span>
                        <span x-text="detail ? clock(detail.received_at) : ''"></span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3" x-show="detail">
                <button type="button" x-show="detail?.html_document" @click="toggleView()"
                        class="min-h-[40px] rounded-lg border-[1.5px] border-line-strong bg-surface px-4 text-base font-bold text-ink hover:bg-paper"
                        x-text="view === 'html' ? strings.viewTextOnly : strings.viewFormatted">{{ __('mailbox.view_text_only') }}</button>
                <button type="button" @click="remove()"
                        class="ml-auto inline-flex min-h-[40px] items-center gap-2 rounded-lg border-[1.5px] border-line-strong bg-surface px-4 text-base font-bold text-ink-soft hover:border-danger hover:text-danger focus-visible:border-danger focus-visible:text-danger">
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 4h11M6 4V2.5h4V4M4 4l.7 9h6.6L12 4M6.8 6.5v4.2M9.2 6.5v4.2"/></svg>
                    {{ __('common.delete') }}
                </button>
            </div>

            <div x-show="detail && view === 'text'" class="min-w-0 break-words rounded-lg border border-line bg-surface p-6 text-[18px] leading-relaxed">
                <p x-show="detail && detail.text_segments.length === 0" class="italic text-ink-soft">{{ __('mailbox.no_text_part') }}</p>
                <template x-for="(segment, index) in (detail?.text_segments ?? [])" :key="index">
                    <span class="whitespace-pre-wrap"><template x-if="segment.type === 'link'"><a :href="segment.value" rel="noopener noreferrer" target="_blank" class="break-all underline" x-text="segment.value"></a></template><template x-if="segment.type !== 'link'"><span x-text="segment.value"></span></template></span>
                </template>
            </div>

            <template x-if="detail && view === 'html' && detail.html_document !== null">
                <iframe class="block min-h-[420px] w-full rounded-lg border border-line bg-surface"
                        sandbox="allow-popups allow-popups-to-escape-sandbox"
                        referrerpolicy="no-referrer"
                        :srcdoc="detail.html_document"
                        title="{{ __('mailbox.mail_body') }}"></iframe>
            </template>
            <p x-show="view === 'html' && detail?.html_document" class="text-[15px] text-ink-soft">{{ __('mailbox.html_hint') }}</p>
        </div>
    </section>
    </div>
</div>
