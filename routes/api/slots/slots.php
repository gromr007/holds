<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SlotController;

Route::group(
    attributes: [
        'as' => '',
        'prefix' => '',
        'middleware' => [],
    ],
    routes: function () {
        Route::controller(SlotController::class)->group(callback: function () {
            Route::get(uri: '/availability', action: 'availability')->name(name: 'availability');
        });

        Route::group(
            attributes: [
                'as' => 'holds.',
                'prefix' => '/{slot_id}/holds',
                'middleware' => [],
            ],
            routes: [
                base_path(path: '/routes/api/slots/holds/holds.php'),
            ]
        );
    }
);
