<x-app-layout>
    @include('admin._nav')

    <div class="space-y-8">
        <x-card>
            <h1 class="text-2xl font-bold text-ink">{{ __('admin.users.title') }}</h1>
            <p class="mt-2 text-base text-ink-soft">{{ trans_choice('admin.users.pending_summary', $pendingCount) }}</p>
        </x-card>

        <x-card>
            <h2 class="text-xl font-bold text-ink">{{ __('admin.settings.title') }}</h2>
            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                @method('PATCH')
                <div class="w-full sm:w-64">
                    <x-input-label for="global_address_limit" :value="__('admin.settings.label')" />
                    <x-text-input id="global_address_limit" name="address_limit" type="number" min="1" required class="mt-2" :value="$defaultLimit" />
                </div>
                <x-primary-button>{{ __('common.save') }}</x-primary-button>
            </form>
            <p class="mt-3 text-[15px] text-ink-soft">{{ __('admin.settings.hint') }}</p>
        </x-card>

        <div class="overflow-hidden rounded-lg border border-line bg-surface">
            @if ($users->isEmpty())
                <p class="p-6 text-base text-ink-soft">{{ __('admin.users.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] border-collapse text-base">
                        <thead class="text-left text-[15px] text-ink-soft">
                            <tr>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_email') }}</th>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_status') }}</th>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_addresses') }}</th>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_limit') }}</th>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_created') }}</th>
                                <th scope="col" class="px-5 py-3 font-bold">{{ __('admin.users.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-t border-line align-top">
                                    <td class="px-5 py-4">
                                        <div class="break-all font-bold text-ink">{{ $user->email }}</div>
                                        @if ($user->isSuperAdmin())
                                            <div class="mt-1 text-[15px] text-accent">{{ __('admin.super_admin') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span @class([
                                            'inline-block rounded-full border px-3 py-0.5 text-[15px] font-bold',
                                            'border-warn bg-warn-soft text-warn-ink' => $user->status === \App\Models\User::STATUS_PENDING,
                                            'border-accent bg-accent-soft text-ink' => $user->status === \App\Models\User::STATUS_ACTIVE,
                                            'border-danger bg-danger-soft text-danger-ink' => in_array($user->status, [\App\Models\User::STATUS_REJECTED, \App\Models\User::STATUS_BLOCKED], true),
                                        ])>{{ __('admin.status.'.$user->status) }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-mono">{{ $user->addresses_count }}</td>
                                    <td class="px-5 py-4">
                                        <div class="font-mono">{{ $user->address_limit ?? __('admin.users.default_limit', ['n' => $defaultLimit]) }}</div>
                                        <form method="POST" action="{{ route('admin.users.limit', $user) }}" class="mt-2 flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" min="0" name="address_limit" value="{{ $user->address_limit }}" placeholder="{{ $defaultLimit }}" aria-label="{{ __('admin.users.limit_label') }}" title="{{ __('admin.users.limit_label') }}"
                                                class="w-28 rounded-lg border-[1.5px] border-line-strong bg-surface px-3 py-2 text-[15px] text-ink focus:border-accent focus:ring-accent">
                                            <x-secondary-button>{{ __('admin.users.limit_save') }}</x-secondary-button>
                                        </form>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-[15px]">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($user->status === \App\Models\User::STATUS_PENDING)
                                                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                    @csrf
                                                    <x-primary-button>{{ __('admin.users.approve') }}</x-primary-button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                                    @csrf
                                                    <x-danger-button>{{ __('admin.users.reject') }}</x-danger-button>
                                                </form>
                                            @elseif ($user->status === \App\Models\User::STATUS_ACTIVE && ! $user->isSuperAdmin())
                                                <form method="POST" action="{{ route('admin.users.block', $user) }}">
                                                    @csrf
                                                    <x-danger-button>{{ __('admin.users.block') }}</x-danger-button>
                                                </form>
                                            @elseif ($user->status === \App\Models\User::STATUS_BLOCKED)
                                                <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                                    @csrf
                                                    <x-secondary-button>{{ __('admin.users.unblock') }}</x-secondary-button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
