<?php

use App\Http\Controllers\Teacher\BeamerController;
use App\Http\Controllers\Teacher\LessonController;
use App\Http\Controllers\Teacher\PinRegenerateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::post('/groups/{group}/lesson', [LessonController::class, 'start'])->name('groups.lesson.start');
    Route::post('/groups/{group}/lesson/extend', [LessonController::class, 'extend'])->name('groups.lesson.extend');
    Route::post('/groups/{group}/lesson/end', [LessonController::class, 'end'])->name('groups.lesson.end');
    Route::post('/groups/{group}/pins', [PinRegenerateController::class, 'group'])->name('groups.pins.regenerate');
    Route::post('/addresses/{address}/pin', [PinRegenerateController::class, 'address'])->name('addresses.pin.regenerate');
    Route::get('/groups/{group}/beamer', [BeamerController::class, 'show'])->name('groups.beamer');
});
