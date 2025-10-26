<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrevisoesController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) {
            return redirect()->route('login');
        }

        $avatarUrl = $this->resolveAvatarUrl($user);

        
        // TODO: Temos que substituir os dados simulados abaixo por suas consultas reais.

        // Dados para a "Análise Descritiva" (Simulados), depois devem ser reais)
        $descriptiveData = (object) [
            'materia_prima' => 'Cacau (Tipo Forasteiro)',
            'volume_compra_ton' => 10,
            'preco_medio_global' => 60.00,
            'preco_medio_brasil' => 43.50,
            'preco_alvo' => 35.00,
        ];
        
        // Dados para a "Tendência do mercado nacional" (Simulados, depois devem ser reais)
        $nationalForecasts = [
            (object) ['mes_ano' => 'Janeiro/2026', 'preco_medio' => 60, 'variacao_perc' => -10.00],
            (object) ['mes_ano' => 'Fevereiro/2026', 'preco_medio' => 63, 'variacao_perc' => 5.00],
            (object) ['mes_ano' => 'Março/2026', 'preco_medio' => 56, 'variacao_perc' => -11.11],
            (object) ['mes_ano' => 'Abril/2026', 'preco_medio' => 52, 'variacao_perc' => -7.14],
        ];
        
        // Dados para o "Comparativo de regiões" (Simulados, depois devem ser reais)
        $regionalComparisons = [
            (object) ['pais' => 'Brasil', 'preco_medio' => 17.80, 'logistica_perc' => 6, 'risco' => 'Médio (Chuvas)', 'estabilidade' => 'Alta', 'ranking' => 1],
            (object) ['pais' => 'Indonésia', 'preco_medio' => 15.40, 'logistica_perc' => 18, 'risco' => 'Alto (Alta umidade)', 'estabilidade' => 'Média', 'ranking' => 3],
            (object) ['pais' => 'Costa do Marfim', 'preco_medio' => 14.90, 'logistica_perc' => 12, 'risco' => 'Alto (Instabilidade)', 'estabilidade' => 'Baixa', 'ranking' => 2],
        ];

        //    A lógica de $stats, $priceRows e $marketOverview foi removida
        //    pois o novo layout não usa esses dados.

        // Retorna a view com as NOVAS variáveis
        return view('previ', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'descriptiveData' => $descriptiveData,
            'nationalForecasts' => $nationalForecasts,
            'regionalComparisons' => $regionalComparisons,
        ]);
    }
}