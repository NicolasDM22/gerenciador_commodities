<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SupportChatController;
use App\Http\Controllers\PrevisoesController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\ProfileModalController;
use Illuminate\Support\Facades\Route;

// Redirecionamento inicial
Route::redirect('/', '/login');

// --- ROTAS PÚBLICAS (Login/Registro) ---
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'authenticate')->name('login.authenticate');
    Route::get('/register', 'create')->name('register');
    Route::post('/register', 'store')->name('register.store');
    Route::post('/logout', 'logout')->name('logout');
});

// --- ROTAS LOGADAS (Usuários comuns e Admins) ---
Route::group([], function () { 
    
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/profile', [HomeController::class, 'update'])->name('profile.update');
    Route::post('/profile', [ProfileModalController::class, 'update'])->name('profile.update');
    
    // Chat de Suporte
    Route::post('/support-chat/open', [SupportChatController::class, 'open'])->name('support-chat.open');
    Route::post('/support-chat/message', [SupportChatController::class, 'send'])->name('support-chat.message');
    Route::post('/support-chat/close', [SupportChatController::class, 'close'])->name('support-chat.close');
    
    // Formulários
    Route::get('/forms', function () { return view('forms'); });
    Route::post('/forms/salvar', [FormsController::class, 'salvar'])->name('forms.salvar');
});

// --- ROTAS ADMINISTRATIVAS ---
Route::middleware(['admin'])->group(function () { 

    // 1. Rotas "Genéricas" (Sem ID na URL)
    Route::get('/previsoes', [PrevisoesController::class, 'index'])->name('forecasts');
    Route::get('/previsoes/graficos', [PrevisoesController::class, 'graficos'])->name('previsoes.graficos');
    Route::get('/previsoes/conclusao', [PrevisoesController::class, 'conclusao'])->name('previsoes.conclusao');

    // 2. Rotas Específicas (Com ID na URL)
    Route::get('/previsoes/graficos/{id}', [PrevisoesController::class, 'graficos'])
        ->where('id', '[0-9]+')
        ->name('previsoes.graficos.show');

    Route::get('/previsoes/conclusao/{id}', [PrevisoesController::class, 'conclusao'])
        ->where('id', '[0-9]+')
        ->name('previsoes.conclusao.show');

    // Exportação PDF
    // Exportação PDF
    Route::get('/previsoes/{id}/exportar-pdf', [PrevisoesController::class, 'exportarPdf'])
        ->where('id', '[0-9]+')
        ->name('previsoes.exportarPdf');

    // A rota principal com ID (Deve ser a última deste grupo para evitar conflitos)
    Route::get('/previsoes/{id}', [PrevisoesController::class, 'index'])
        ->where('id', '[0-9]+')
        ->name('previsoes.show'); 

    // Notificações Admin
    Route::get('/admin/notificacoes', [AdminNotificationController::class, 'index'])->name('admin.notifications');
    Route::post('/admin/notificacoes/{notificationId}/lida', [AdminNotificationController::class, 'markAsRead'])->name('admin.notifications.read');
});