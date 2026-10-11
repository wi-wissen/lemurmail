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
            'secretNone' => __('mailbox.secret_none'),
            'secretMask' => '••••••••••',
            'secretShow' => __('mailbox.secret_show'),
            'secretHide' => __('mailbox.secret_hide'),
        ],
    ]);
@endphp

<div x-data="inbox(@js($inbox))" class="flex flex-1 flex-col lg:flex-row">
    <section class="flex flex-col border-b border-line bg-surface lg:w-[360px] lg:shrink-0 lg:border-b-0 lg:border-r"
             aria-label="{{ __('mailbox.inbox_title') }}">
        <p class="border-b border-line px-6 py-4 text-[15px] text-ink-soft" x-text="countText">{{ __('mailbox.messages_loading') }}</p>

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
                <x-danger-button type="button" x-show="detail" @click="remove()">{{ __('common.delete') }}</x-danger-button>
            </div>

            <div class="flex flex-wrap items-center gap-3" x-show="detail">
                <button type="button" @click="view = 'text'"
                        class="min-h-[40px] rounded-lg border-[1.5px] px-4 text-base font-bold"
                        :class="view === 'text' ? 'border-ink bg-ink text-white' : 'border-line-strong bg-surface text-ink hover:bg-paper'"
                        :aria-pressed="view === 'text' ? 'true' : 'false'">{{ __('mailbox.view_text') }}</button>
                <button type="button" @click="view = 'html'" x-show="detail?.html_document"
                        class="min-h-[40px] rounded-lg border-[1.5px] px-4 text-base font-bold"
                        :class="view === 'html' ? 'border-ink bg-ink text-white' : 'border-line-strong bg-surface text-ink hover:bg-paper'"
                        :aria-pressed="view === 'html' ? 'true' : 'false'">{{ __('mailbox.view_html') }}</button>
                <p x-show="view === 'html'" class="text-[15px] text-ink-soft">{{ __('mailbox.html_hint') }}</p>
            </div>

            <div x-show="detail && view === 'text'" class="min-w-0 break-words rounded-lg border border-line bg-surface p-6 text-[18px] leading-relaxed">
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
        </div>

        <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-line bg-surface p-5">
            <div class="min-w-0 space-y-1">
                <p class="text-base font-bold">{{ __('mailbox.secret_title') }}</p>
                <p class="break-all font-mono text-lg tracking-[2px]" x-text="secretText">{{ $hasSecret ? '••••••••••' : __('mailbox.secret_none') }}</p>
                <p class="text-[15px] text-ink-soft">{{ __('mailbox.secret_hint') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" x-show="hasSecret" @click="toggleSecret()"
                        class="min-h-[44px] rounded-lg border-[1.5px] border-accent px-4 text-base font-bold text-accent hover:bg-accent-soft"
                        x-text="secretVisible ? strings.secretHide : strings.secretShow">{{ __('mailbox.secret_show') }}</button>
                <a href="{{ $secretEditUrl }}"
                   class="inline-flex min-h-[44px] items-center rounded-lg border-[1.5px] border-accent px-4 text-base font-bold text-accent hover:bg-accent-soft">{{ __('mailbox.secret_change') }}</a>
            </div>
        </div>
    </section>
</div>
