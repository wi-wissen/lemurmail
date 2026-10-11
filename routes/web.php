<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Language switch (stored in the session, read by App\Http\Middleware\SetLocale).
Route::get('/lang/{locale}', LocaleController::class)->name('locale');

// Each area owns its route file.
require __DIR__.'/mailbox.php';   // students: "/" (name: home), inbox, secret
require __DIR__.'/auth.php';      // teachers: login, registration, verification, account status, profile
require __DIR__.'/teacher.php';   // groups, addresses, senders, CSV/print
require __DIR__.'/lesson.php';    // lesson control, PIN regeneration, beamer
require __DIR__.'/admin.php';     // super admin

// Breeze redirects to "dashboard" after login; teachers land on their groups.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
