<x-app-layout>
    <div class="flex flex-col gap-6">
        <h1 class="text-[30px] font-bold leading-tight">{{ __('account.profile_title') }}</h1>

        <x-card class="max-w-2xl">
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card class="max-w-2xl">
            @include('profile.partials.update-password-form')
        </x-card>

        <x-card class="max-w-2xl">
            @include('profile.partials.delete-user-form')
        </x-card>
    </div>
</x-app-layout>
