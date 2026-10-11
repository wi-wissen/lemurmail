<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function update(SettingsRequest $request): RedirectResponse
    {
        Setting::put('address_limit', $request->integer('address_limit'));

        return back()->with('status', __('admin.settings.saved'));
    }
}
