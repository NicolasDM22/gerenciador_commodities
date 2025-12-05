<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\PrevisoesController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\ProfileModalController;
use App\Http\Controllers\UserAnalysisController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
// by Nicolas Duran Munhos, Matias Amma e Gustavo Cavalheiro
// // Rotas públicas (login/registro)
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate')->name('login.authenticate');
    Route::get('/register', 'create')->name('register');
    Route::post('/register', 'store')->name('register.store');
    Route::post('/logout', 'logout')->name('logout');
});

// Rotas logadas (todos os usuários)
Route::group([], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/profile', [HomeController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileModalController::class, 'update'])->name('profile.update');

    // Chat de suporte
    Route::post('/support-chat/open', [SupportChatController::class, 'open'])->name('support-chat.open');
    Route::post('/support-chat/message', [SupportChatController::class, 'send'])->name('support-chat.message');
    Route::post('/support-chat/close', [SupportChatController::class, 'close'])->name('support-chat.close');

    // Formulários
    Route::get('/forms', function () { return view('forms'); });
    Route::post('/forms/salvar', [FormsController::class, 'salvar'])->name('forms.salvar');

    // Detalhes de análises automáticas (view dedicada)
    Route::get('/analises/{id}', [UserAnalysisController::class, 'show'])
        ->where('id', '[0-9]+')
        ->name('analises.show');

    // Detalhes nativos de previsões (mesmo layout da área admin)
    Route::get('/previsoes/{id}', [PrevisoesController::class, 'index'])
        ->where('id', '[0-9]+')
        ->name('previsoes.show');
    Route::delete('/previsoes/{id}', [PrevisoesController::class, 'destroy'])
        ->where('id', '[0-9]+')
        ->name('previsoes.destroy');

    Route::get('/previsoes/graficos', [PrevisoesController::class, 'graficos'])->name('previsoes.graficos');
    Route::get('/previsoes/graficos/{id}', [PrevisoesController::class, 'graficos'])
        ->where('id', '[0-9]+')
        ->name('previsoes.graficos.show');

    Route::get('/previsoes/conclusao', [PrevisoesController::class, 'conclusao'])->name('previsoes.conclusao');
    Route::get('/previsoes/conclusao/{id}', [PrevisoesController::class, 'conclusao'])
        ->where('id', '[0-9]+')
        ->name('previsoes.conclusao.show');

    Route::get('/previsoes/{id}/exportar-pdf', [PrevisoesController::class, 'exportarPdf'])
        ->where('id', '[0-9]+')
        ->name('previsoes.exportarPdf');
});

// Rotas exclusivas de administradores
Route::middleware(['admin'])->group(function () {
    Route::get('/previsoes', [PrevisoesController::class, 'index'])->name('forecasts');

    Route::get('/admin/notificacoes', [AdminNotificationController::class, 'index'])->name('admin.notifications');
    Route::post('/admin/notificacoes/{notificationId}/lida', [AdminNotificationController::class, 'markAsRead'])->name('admin.notifications.read');
});
