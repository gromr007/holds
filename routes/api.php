<?php

use Illuminate\Support\Facades\Route;

Route::group(
    attributes: [
        'as' => 'api.',
        'prefix' => '',
        'middleware' => [
            'api'
        ],
    ],
    routes: function () {

        Route::group(
            attributes: [
                'as' => 'slots.',
                'prefix' => '/slots',
                'middleware' => [],
            ],
            routes: [
                base_path(path: '/routes/api/slots/slots.php'),
            ]
        );

    }
);
