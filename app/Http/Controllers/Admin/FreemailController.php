<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FreemailRequest;
use App\Models\BlockedDomain;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FreemailController extends Controller
{
    public function index(): View
    {
        return view('admin.freemail.index', [
            'blockedDomains' => BlockedDomain::orderBy('domain')->get(),
        ]);
    }

    public function store(FreemailRequest $request): RedirectResponse
    {
        BlockedDomain::firstOrCreate(['domain' => $request->validated('domain')]);

        return back()->with('status', __('admin.domains.saved'));
    }

    public function destroy(BlockedDomain $blockedDomain): RedirectResponse
    {
        $blockedDomain->delete();

        return back()->with('status', __('admin.domains.removed'));
    }
}
