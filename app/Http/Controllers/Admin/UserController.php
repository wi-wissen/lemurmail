<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddressLimitRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AccountDecision;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->withCount('addresses')
                ->orderByRaw('status = ? DESC', [User::STATUS_PENDING])
                ->orderByDesc('created_at')
                ->get(),
            'pendingCount' => User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('status', User::STATUS_PENDING)
                ->count(),
            'defaultLimit' => Setting::getInt('address_limit', config('lemurmail.default_address_limit')),
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

    private function refuse(string $message): RedirectResponse
    {
        return back()->withErrors(['status' => __($message)]);
    }
}
