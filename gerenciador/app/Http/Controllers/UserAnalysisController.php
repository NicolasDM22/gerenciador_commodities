<?php
/**
 * UserAnalysisController.php by Nicolas Duran Munhos & Matias Amma
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserAnalysisController extends Controller
{
    /**
     * Exibe uma analise da IA para o usuario: valida sessao/permissao,
     * busca log especifico, decodifica resposta e renderiza view com dados parseados.
     */
    public function show(Request $request, int $id)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        $log = DB::table('ai_analysis_logs')->where('id', $id)->first();
        if (!$log) abort(404);

        $isAdmin = (bool) $request->session()->get('auth_is_admin', false);
        if (!$isAdmin && $log->user_id !== $userId) {
            return redirect()->route('home')->withErrors('Acesso negado.');
        }

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin')
            ->where('id', $userId)
            ->first();

        if (!$user) return redirect()->route('login');

        $parsed = json_decode($log->response ?? '', true) ?: [];
        $createdAt = $log->created_at ? Carbon::parse($log->created_at)->format('d/m/Y H:i') : '-';

        return view('analysis-show', [
            'user' => $user,
            'avatarUrl' => $this->resolveAvatarUrl($user),
            'analysis' => $log,
            'parsed' => $parsed,
            'mercados' => $parsed['mercados'] ?? [],
            'indicadores' => $parsed['indicadores'] ?? [],
            'logistica' => $parsed['logistica'] ?? [],
            'recomendacao' => $parsed['recomendacao'] ?? null,
            'created_at' => $createdAt,
            'isAdmin' => $isAdmin,
        ]);
    }
}
