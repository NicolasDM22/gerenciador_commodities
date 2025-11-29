<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'telefone', 'endereco', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $avatarUrl = $this->resolveAvatarUrl($user);
        // --- ALTERAÇÃO AQUI ---
        // Removemos o ->limit(5) para que o DataTables receba TUDO e possa filtrar corretamente.
        // Adicionamos orderBy para mostrar os mais recentes primeiro.
        $analysis = DB::table('previsoes')
            ->select('id', 'commodity_nome', 'data_previsao', 'acao')
            ->orderByDesc('data_previsao') // Opcional: ordena por data
            // ->limit(5) <--- REMOVIDO
            ->get();

        $chartData = [
            'labels' => ['09/25', '10/25', '11/25', '12/25', '01/26', '02/26', '03/26', '04/26'],
            'prices' => [60, 58, 57, 57.5, 59, 62, 56, 52],
            'commodityName' => 'AAAA'
        ];
        
        $isAdmin = (bool) ($user->is_admin ?? false);
        $adminData = ['notifications' => collect()];

        if ($isAdmin) {
            $adminData['notifications'] = DB::table('admin_notifications')
                ->select('id', 'title', 'body', 'status', 'created_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(function ($notification) {
                    $createdAt = $notification->created_at ? Carbon::parse($notification->created_at) : null;
                    $notification->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Nao informado';
                    return $notification;
                });
        }

        return view('home', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'chartData' => $chartData,
            'isAdmin' => $isAdmin,
            'adminData' => $adminData,
            'previousAnalyses' => $analysis,
        ]);
    }
}