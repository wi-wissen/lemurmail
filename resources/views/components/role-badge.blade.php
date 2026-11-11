{{-- Marks who a page is for: "student" (mailbox) or "teacher" (account). --}}
@props(['role'])
<span {{ $attributes->class([
    'inline-flex items-center gap-2 self-start rounded-full px-3 py-1 text-[15px] font-bold',
    'bg-accent-soft text-accent-hover' => $role === 'student',
    'border border-line bg-paper text-ink-soft' => $role !== 'student',
]) }}>
    @if ($role === 'student')
        <x-icons.inbox class="h-[18px] w-[18px]" />
    @else
        <x-icons.users class="h-[18px] w-[18px]" />
    @endif
    {{ __($role === 'student' ? 'common.role_student' : 'common.role_teacher') }}
</span>
