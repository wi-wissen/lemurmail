<?php

use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\FreemailController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    Route::post('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
    Route::post('/users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
    Route::patch('/users/{user}/limit', [UserController::class, 'limit'])->name('users.limit');

    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains/organization', [DomainController::class, 'storeOrganization'])->name('domains.organization.store');
    Route::delete('/domains/organization/{organizationDomain}', [DomainController::class, 'destroyOrganization'])->name('domains.organization.destroy');

    Route::get('/freemail', [FreemailController::class, 'index'])->name('freemail.index');
    Route::post('/freemail', [FreemailController::class, 'store'])->name('freemail.store');
    Route::delete('/freemail/{blockedDomain}', [FreemailController::class, 'destroy'])->name('freemail.destroy');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');
});
