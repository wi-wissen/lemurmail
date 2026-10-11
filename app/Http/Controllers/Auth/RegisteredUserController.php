<?php

namespace App\Http\Controllers\Auth;

use App\Core\Registration\DomainClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => strstr($request->string('email')->value(), '@', true),
            'email' => $request->string('email')->value(),
            'password' => Hash::make($request->string('password')->value()),
            'role' => User::ROLE_TEACHER,
            'status' => $request->domainClass() === DomainClass::Approved ? User::STATUS_ACTIVE : User::STATUS_PENDING,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
