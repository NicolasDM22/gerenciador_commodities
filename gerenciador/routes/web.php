<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate')->name('login.authenticate');
});
