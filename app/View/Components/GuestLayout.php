<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout of the public pages: the start page (wide) and every sign-in page (narrow card).
 */
class GuestLayout extends Component
{
    public function __construct(public bool $wide = false) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
