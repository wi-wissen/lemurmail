<x-app-layout>
    @include('admin._nav')

    <div class="space-y-8">
        <h1 class="text-2xl font-bold text-ink">{{ __('admin.nav.settings') }}</h1>

        <x-card>
            <h2 class="text-xl font-bold text-ink">{{ __('admin.settings.title') }}</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                @method('PATCH')
                <div class="w-full sm:w-64">
                    <x-input-label for="address_limit" :value="__('admin.settings.label')" />
                    <x-text-input id="address_limit" name="address_limit" type="number" min="1" required class="mt-2" :value="$limit" />
                </div>
                <x-primary-button>{{ __('common.save') }}</x-primary-button>
            </form>
            <p class="mt-3 text-[15px] text-ink-soft">{{ __('admin.settings.hint') }}</p>
        </x-card>
    </div>
</x-app-layout>
