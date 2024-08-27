<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors', 'json.response']], function () {

    // public routes

    // protected routes
    Route::middleware('auth:api')->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout.api');
    });
});
