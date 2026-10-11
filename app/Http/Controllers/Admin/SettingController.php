<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'limit' => Setting::getInt('address_limit', config('lemurmail.default_address_limit')),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        Setting::put('address_limit', $request->integer('address_limit'));

        return redirect()->route('admin.settings.edit')->with('status', __('admin.settings.saved'));
    }
}
