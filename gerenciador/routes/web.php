<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\PrevisoesController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\ProfileModalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate')->name('login.authenticate');

    Route::get('/register', 'create')->name('register');
    Route::post('/register', 'store')->name('register.store');

    Route::post('/logout', 'logout')->name('logout');
});

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::post('/support-chat/open', [SupportChatController::class, 'open'])->name('support-chat.open');
Route::post('/support-chat/message', [SupportChatController::class, 'send'])->name('support-chat.message');
Route::post('/support-chat/close', [SupportChatController::class, 'close'])->name('support-chat.close');
Route::get('/admin/notificacoes', [AdminNotificationController::class, 'index'])->name('admin.notifications');
Route::post('/admin/notificacoes/{notificationId}/lida', [AdminNotificationController::class, 'markAsRead'])->name('admin.notifications.read');
Route::get('/previsoes', [PrevisoesController::class, 'index'])->name('forecasts');
Route::get('/previsoes/graficos', [PrevisoesController::class, 'graficos'])->name('previsoes.graficos');
Route::get('/previsoes/conclusao', [PrevisoesController::class, 'conclusao'])->name('previsoes.conclusao');
Route::get('/forms', function () {return view('forms');});
Route::post('/forms/salvar', [FormsController::class, 'salvar'])->name('forms.salvar');
Route::get('/profile', function () {return view('profile');});
Route::post('/profile/update', [ProfileModalController::class, 'update'])->name('profile.update');
