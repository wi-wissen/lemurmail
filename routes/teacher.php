<?php

use Illuminate\Support\Facades\Route;

// PLACEHOLDER: replaced by the teacher work package.
Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/groups', fn () => '')->name('groups.index');
    Route::get('/senders', fn () => '')->name('senders.index');
});
