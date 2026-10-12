<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DomainRequest;
use App\Models\OrganizationDomain;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DomainController extends Controller
{
    public function index(): View
    {
        return view('admin.domains.index', [
            'organizationDomains' => OrganizationDomain::orderBy('domain')->orderBy('local_part_pattern')->get(),
        ]);
    }

    public function storeOrganization(DomainRequest $request): RedirectResponse
    {
        OrganizationDomain::firstOrCreate([
            'domain' => $request->validated('domain'),
            'local_part_pattern' => $request->validated('pattern'),
        ]);

        return back()->with('status', __('admin.domains.saved'));
    }

    public function destroyOrganization(OrganizationDomain $organizationDomain): RedirectResponse
    {
        $organizationDomain->delete();

        return back()->with('status', __('admin.domains.removed'));
    }
}
