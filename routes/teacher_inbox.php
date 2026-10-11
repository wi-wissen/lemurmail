<?php

use App\Http\Controllers\Teacher\AddressInboxController;
use App\Http\Controllers\Teacher\AddressSecretController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/addresses/{address}/inbox', [AddressInboxController::class, 'inbox'])->name('addresses.inbox');
    Route::get('/addresses/{address}/messages', [AddressInboxController::class, 'messages'])->name('addresses.messages');
    Route::get('/addresses/{address}/messages/{message}', [AddressInboxController::class, 'show'])->name('addresses.messages.show');
    Route::delete('/addresses/{address}/messages/{message}', [AddressInboxController::class, 'destroy'])->name('addresses.messages.destroy');

    Route::get('/addresses/{address}/secret', [AddressSecretController::class, 'edit'])->name('addresses.secret.edit');
    Route::put('/addresses/{address}/secret', [AddressSecretController::class, 'update'])->name('addresses.secret.update');
    Route::get('/addresses/{address}/secret/value', [AddressSecretController::class, 'show'])->name('addresses.secret.show');
});
