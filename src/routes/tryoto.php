<?php

use Illuminate\Support\Facades\Route;
use Siberfx\LaravelTryoto\app\Http\Controllers\Api\TryOtoController;


Route::group(['prefix' => 'tryoto'], function () {
    Route::get('set-webhook', [TryOtoController::class, 'setWebhook'])->name('tryoto.set-webhook');
    Route::post('webhook/callback', [TryOtoController::class, 'listenWebhook'])->name('tryoto.callback');

});

