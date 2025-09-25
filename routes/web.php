<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

// Dashboard principal
Route::get('/', [WebhookController::class, 'dashboard'])->name('dashboard');

// Endpoint para receber webhooks (POST e PUT)
Route::match(['post', 'put'], '/webhook', [WebhookController::class, 'receive'])->name('webhook.receive');

// API endpoints para o dashboard
Route::get('/api/webhooks', [WebhookController::class, 'getWebhooks'])->name('api.webhooks');
Route::get('/api/webhooks/{id}', [WebhookController::class, 'show'])->name('api.webhook.show');
Route::post('/api/webhooks/clear', [WebhookController::class, 'clear'])->name('api.webhooks.clear');
