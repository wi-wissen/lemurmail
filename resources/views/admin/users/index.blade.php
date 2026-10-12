@php
    use App\Http\Controllers\Admin\UserController;
    use App\Models\User;

    $columns = [
        'email' => __('admin.users.col_email'),
        'status' => __('admin.users.col_status'),
        'addresses' => __('admin.users.col_addresses'),
        'limit' => __('admin.users.col_limit'),
        'created' => __('admin.users.col_created'),
        'actions' => __('admin.users.col_actions'),
    ];
    $statusClasses = [
        User::STATUS_PENDING => 'border-warn bg-warn-soft text-warn-ink',
        User::STATUS_ACTIVE => 'border-accent bg-accent-soft text-ink',
        User::STATUS_REJECTED => 'border-danger bg-danger-soft text-danger-ink',
        User::STATUS_BLOCKED => 'border-danger bg-danger-soft text-danger-ink',
    ];
@endphp
<x-app-layout>
    @include('admin._nav')

    <div class="space-y-8">
        <x-card>
            <h1 class="text-2xl font-bold text-ink">{{ __('admin.users.title') }}</h1>
            <p class="mt-2 text-base text-ink-soft">{{ trans_choice('admin.users.pending_summary', $pendingCount) }}</p>
        </x-card>

        <div class="overflow-hidden rounded-lg border border-line bg-surface">
            @if ($users->isEmpty())
                <p class="p-6 text-base text-ink-soft">{{ __('admin.users.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] border-collapse text-[15px]">
                        <thead class="text-left text-ink-soft">
                            <tr>
                                @foreach ($columns as $column => $label)
                                    @php($startDir = UserController::SORTS[$column] ?? null)
                                    @if ($startDir === null)
                                        <th scope="col" class="whitespace-nowrap px-3 py-3 font-bold">{{ $label }}</th>
                                    @else
                                        @php($active = $sort === $column)
                                        <th scope="col" class="whitespace-nowrap px-3 py-3 font-bold" @if ($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                            <a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort' => $column, 'dir' => $active ? ($dir === 'asc' ? 'desc' : 'asc') : $startDir])) }}"
                                                class="inline-flex min-h-[36px] items-center gap-1.5 rounded text-ink-soft hover:text-ink hover:underline focus-visible:text-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                                {{ $label }}
                                                @if ($active)
                                                    <svg class="h-3.5 w-3.5 {{ $dir === 'desc' ? 'rotate-180' : '' }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 10.5 8 6l4.5 4.5"/></svg>
                                                @else
                                                    <svg class="h-3.5 w-3.5 opacity-40" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 6.5 8 3.5l3 3M5 9.5l3 3 3-3"/></svg>
                                                @endif
                                            </a>
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-t border-line">
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <div class="max-w-[14rem] truncate font-bold text-ink" title="{{ $user->email }}">{{ $user->email }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block rounded-full border px-3 py-0.5 font-bold {{ $statusClasses[$user->status] }}">{{ __('admin.status.'.$user->status) }}</span>
                                            @if ($user->isSuperAdmin())
                                                <span data-badge="super-admin" class="inline-block rounded-full border border-ink-soft bg-paper px-3 py-0.5 font-bold text-ink">{{ __('admin.super_admin') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 font-mono tabular-nums">{{ $user->addresses_count }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <form method="POST" action="{{ route('admin.users.limit', $user) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" min="0" name="address_limit" value="{{ $user->address_limit }}" placeholder="{{ $defaultLimit }}" aria-label="{{ __('admin.users.limit_label') }}"
                                                class="h-10 w-20 rounded-lg border-[1.5px] border-line-strong bg-surface px-2 font-mono text-ink placeholder:text-ink-soft focus:border-accent focus:ring-accent">
                                            <button type="submit" aria-label="{{ __('admin.users.limit_save') }}" title="{{ __('admin.users.limit_save') }}"
                                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border-[1.5px] border-line-strong bg-surface text-ink hover:border-accent hover:bg-accent-soft hover:text-accent focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5l4 4 8-9"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 font-mono tabular-nums">{{ $user->created_at->isoFormat('L') }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            @if ($user->status === User::STATUS_PENDING)
                                                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex min-h-[36px] items-center rounded-lg bg-accent px-3 text-[15px] font-bold text-white hover:bg-accent-hover">{{ __('admin.users.approve') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.users.reject', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="min-h-[36px] px-2 text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('admin.users.reject') }}</button>
                                                </form>
                                            @elseif ($user->status === User::STATUS_ACTIVE && ! $user->isSuperAdmin())
                                                <form method="POST" action="{{ route('admin.users.block', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="min-h-[36px] px-2 text-[15px] font-bold text-ink-soft underline hover:text-danger focus-visible:text-danger">{{ __('admin.users.block') }}</button>
                                                </form>
                                            @elseif ($user->status === User::STATUS_BLOCKED)
                                                <form method="POST" action="{{ route('admin.users.unblock', $user) }}">
                                                    @csrf
                                                    <button type="submit" class="inline-flex min-h-[36px] items-center rounded-lg border-[1.5px] border-line-strong bg-surface px-3 text-[15px] font-bold text-ink hover:bg-paper">{{ __('admin.users.unblock') }}</button>
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
