<x-app-layout>
    <div class="mx-auto flex max-w-2xl flex-col gap-6">
        <div>
            <h1 class="mb-2 text-3xl font-bold leading-tight">{{ __('common.nav_senders') }}</h1>
            <p class="text-lg text-ink-soft">{{ __('teacher.senders.intro') }}</p>
        </div>
        @include('teacher.senders._list', ['rules' => $rules])
    </div>
</x-app-layout>
