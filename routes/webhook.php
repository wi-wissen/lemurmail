<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/lettermint', WebhookController::class)->name('webhooks.lettermint');
