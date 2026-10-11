<?php

use App\Http\Controllers\Mailbox\HomeController;
use App\Http\Controllers\Mailbox\InboxController;
use App\Http\Controllers\Mailbox\LoginController;
use App\Http\Controllers\Mailbox\SecretController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/mailbox/login', [LoginController::class, 'store'])->name('mailbox.login');
Route::post('/mailbox/logout', [LoginController::class, 'destroy'])->name('mailbox.logout');

Route::middleware('mailbox')->group(function () {
    Route::get('/mailbox', [InboxController::class, 'index'])->name('mailbox.inbox');
    Route::get('/mailbox/messages', [InboxController::class, 'messages'])->name('mailbox.messages');
    Route::get('/mailbox/messages/{message}', [InboxController::class, 'show'])->name('mailbox.messages.show');
    Route::delete('/mailbox/messages/{message}', [InboxController::class, 'destroy'])->name('mailbox.messages.destroy');

    Route::get('/mailbox/secret', [SecretController::class, 'edit'])->name('mailbox.secret.edit');
    Route::put('/mailbox/secret', [SecretController::class, 'update'])->name('mailbox.secret.update');
    Route::get('/mailbox/secret/value', [SecretController::class, 'show'])->name('mailbox.secret.show');
});
