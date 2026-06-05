<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HoldController;
use Illuminate\Support\Facades\Route;

Route::group(
    attributes: [
        'as' => '',
        'prefix' => '',
        'middleware' => [],
    ],
    routes: function () {
        Route::controller(HoldController::class)->group(callback: function () {
            Route::post(uri: '/', action: 'hold')->name(name: 'store');
            Route::prefix('/{hold_id}')->whereNumber('hold_id')->group(callback: function () {
                Route::get(uri: '/', action: 'show')->name(name: 'show');
                Route::patch(uri: '/', action: 'confirm')->name(name: 'confirm');
                Route::delete(uri: '/', action: 'cancel')->name(name: 'destroy');
            });
        });
    }
);
