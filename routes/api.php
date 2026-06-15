<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
| Webhook publik untuk menerima response async dari CEISA.
| Tidak pakai auth session; diverifikasi via shared secret (CEISA_WEBHOOK_SECRET).
*/
Route::post('/webhook/ceisa', [WebhookController::class, 'ceisa'])
    ->name('webhook.ceisa');
