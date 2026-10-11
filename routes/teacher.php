<?php

use App\Http\Controllers\Teacher\AddressController;
use App\Http\Controllers\Teacher\GroupController;
use App\Http\Controllers\Teacher\PinExportController;
use App\Http\Controllers\Teacher\SenderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('/groups/{group}/addresses', [AddressController::class, 'storeSeries'])->name('groups.addresses.store');
    Route::get('/groups/{group}/pins.csv', [PinExportController::class, 'csv'])->name('groups.pins.csv');
    Route::get('/groups/{group}/print', [PinExportController::class, 'print'])->name('groups.print');

    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/addresses/{address}/senders', [SenderController::class, 'storeForAddress'])->name('addresses.senders.store');

    Route::get('/senders', [SenderController::class, 'index'])->name('senders.index');
    Route::post('/senders', [SenderController::class, 'store'])->name('senders.store');
    Route::delete('/senders/{rule}', [SenderController::class, 'destroy'])->name('senders.destroy');
});
