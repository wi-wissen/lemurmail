<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddressLimitRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountDecision;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /** Sortable columns with the direction they start with. */
    public const SORTS = [
        'email' => 'asc',
        'status' => 'asc',
        'addresses' => 'desc',
        'created' => 'asc',
    ];

    public function index(Request $request): View
    {
        [$sort, $dir] = $this->sorting($request);

        return view('admin.users.index', [
            'users' => $this->sortedUsers($sort, $dir)->get(),
            'pendingCount' => User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('status', User::STATUS_PENDING)
                ->count(),
            'defaultLimit' => Setting::getInt('address_limit', config('lemurmail.default_address_limit')),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING) {
            return $this->refuse('admin.errors.invalid_transition');
        }

        $user->update(['status' => User::STATUS_ACTIVE]);
        $user->notify(new AccountDecision(approved: true));

        return back()->with('status', __('admin.users.approved'));
    }

    public function reject(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING) {
            return $this->refuse('admin.errors.invalid_transition');
        }

        $user->update(['status' => User::STATUS_REJECTED]);
        $user->notify(new AccountDecision(approved: false));

        return back()->with('status', __('admin.users.rejected'));
    }

    public function block(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return $this->refuse('admin.errors.super_admin_block');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            return $this->refuse('admin.errors.invalid_transition');
        }

        $user->update(['status' => User::STATUS_BLOCKED]);

        return back()->with('status', __('admin.users.blocked'));
    }

    public function unblock(User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_BLOCKED) {
            return $this->refuse('admin.errors.invalid_transition');
        }

        $user->update(['status' => User::STATUS_ACTIVE]);

        return back()->with('status', __('admin.users.unblocked'));
    }

    public function limit(AddressLimitRequest $request, User $user): RedirectResponse
    {
        $user->update(['address_limit' => $request->validated('address_limit')]);

        return back()->with('status', __('admin.users.limit_saved'));
    }

    /** @return array{0: ?string, 1: string} */
    private function sorting(Request $request): array
    {
        $column = $request->query('sort');

        if (! is_string($column) || ! array_key_exists($column, self::SORTS)) {
            return [null, 'asc'];
        }

        $dir = $request->query('dir');

        return [$column, in_array($dir, ['asc', 'desc'], true) ? $dir : self::SORTS[$column]];
    }

    private function sortedUsers(?string $sort, string $dir): Builder
    {
        $query = User::query()->withCount('addresses');

        return match ($sort) {
            'email' => $query->orderBy('email', $dir),
            'created' => $query->orderBy('created_at', $dir)->orderBy('email'),
            'addresses' => $query->orderBy('addresses_count', $dir)->orderBy('email'),
            'status' => $query
                ->orderByRaw(
                    'CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 END '.$dir,
                    [User::STATUS_PENDING, User::STATUS_ACTIVE, User::STATUS_REJECTED, User::STATUS_BLOCKED],
                )
                ->orderBy('email'),
            default => $query
                ->orderByRaw('status = ? DESC', [User::STATUS_PENDING])
                ->orderByDesc('created_at'),
        };
    }

    private function refuse(string $message): RedirectResponse
    {
        return back()->withErrors(['status' => __($message)]);
    }
}
