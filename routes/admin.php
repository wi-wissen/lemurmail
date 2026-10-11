<?php

use Illuminate\Support\Facades\Route;

// PLACEHOLDER: replaced by the auth/admin work package.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', fn () => '')->name('users.index');
});
