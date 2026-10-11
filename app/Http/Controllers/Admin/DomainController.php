<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRequest;
use App\Models\BlockedDomain;
use App\Models\OrganizationDomain;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DomainController extends Controller
{
    public function index(): View
    {
        return view('admin.domains.index', [
            'organizationDomains' => OrganizationDomain::orderBy('domain')->get(),
            'blockedDomains' => BlockedDomain::orderBy('domain')->get(),
        ]);
    }

    public function storeOrganization(DomainRequest $request): RedirectResponse
    {
        OrganizationDomain::firstOrCreate(['domain' => $request->validated('domain')]);

        return back()->with('status', __('admin.domains.saved'));
    }

    public function destroyOrganization(OrganizationDomain $organizationDomain): RedirectResponse
    {
        $organizationDomain->delete();

        return back()->with('status', __('admin.domains.removed'));
    }

    public function storeFreemail(DomainRequest $request): RedirectResponse
    {
        BlockedDomain::firstOrCreate(['domain' => $request->validated('domain')]);

        return back()->with('status', __('admin.domains.saved'));
    }

    public function destroyFreemail(BlockedDomain $blockedDomain): RedirectResponse
    {
        $blockedDomain->delete();

        return back()->with('status', __('admin.domains.removed'));
    }
}
