<?php

Route::group('api', function () {

    /**
     * API version 3.0
     */
    Route::group('v3', function () {
        Route::get('/', function () {
            return 'home';
        });

    });

    /**
     * API Monitor
     */
    Route::get('monitor', function () {
        return 'ok';
    });
});
