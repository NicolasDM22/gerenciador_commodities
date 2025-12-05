<?php

namespace App\Http\Middleware;
//by Matias Amma e Nicolas Duran
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Só roda se for rota de previsões ou admin
        if (!$request->is('previsoes*') && !$request->is('admin*')) {
            return $next($request);
        }

        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            // Se não tem ID na sessão, vai pro login
            return redirect()->route('login');
        }

        // 3. Verifica se é Admin
        $user = DB::table('users')->where('id', $userId)->first();

        if ($user && $user->is_admin == 1) {
            return $next($request);
        }

        return redirect()->route('home')
        ->withErrors(['Acesso negado: Você não tem permissão para acessar esta página.']);
    }
}