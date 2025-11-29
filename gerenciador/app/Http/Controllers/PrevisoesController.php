<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrevisoesController extends Controller
{
    /**
     * Método principal (Dashboard/Descritivo)
     * Rota: /previsoes ou /previsoes/{id}
     */
    public function index(Request $request, $id = null)
    {
        // 1. Autenticação
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodity = null;

        // 2. Lógica de Seleção da Commodity (Hierarquia Rígida)

        // CASO 1: ID veio na Rota (/previsoes/2)
        if ($id) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->where('id', $id)
                ->first();
        }

        // CASO 2: ID veio na Query String (/previsoes?commodity_id=2)
        // Só tenta se não achou no CASO 1
        if (!$commodity && $request->query('commodity_id')) {
             $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->where('id', $request->query('commodity_id'))
                ->first();
        }

        // CASO 3: Fallback (Modo Padrão - Última atualizada)
        // Só tenta se não achou nem no CASO 1 nem no CASO 2
        if (!$commodity) {
            $latestMetrics = DB::table('commodity_descriptive_metrics as metrics')
                ->select('metrics.commodity_id')
                ->orderByDesc('metrics.referencia_mes')
                ->orderByDesc('metrics.updated_at')
                ->orderByDesc('metrics.created_at')
                ->first();

            if ($latestMetrics) {
                $commodity = DB::table('commodities')
                    ->select('id', 'nome', 'categoria', 'unidade')
                    ->where('id', $latestMetrics->commodity_id)
                    ->first();
            }
        }

        // CASO 4: Último recurso (Primeira alfabética)
        if (!$commodity) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->orderBy('nome')
                ->first();
        }

        // Erro fatal se banco estiver vazio
        if (!$commodity) {
            return redirect()->route('home')->withErrors('Nenhuma commodity cadastrada.');
        }

        // 3. Buscar Dados Descritivos
        $descriptiveData = DB::table('commodity_descriptive_metrics as metrics')
            ->select(
                'metrics.volume_compra_ton',
                'metrics.preco_medio_global',
                'metrics.preco_medio_brasil',
                'metrics.preco_alvo',
                'metrics.referencia_mes',
                'commodities.nome as materia_prima'
            )
            ->join('commodities', 'commodities.id', '=', 'metrics.commodity_id')
            ->where('metrics.commodity_id', $commodity->id)
            ->orderByDesc('metrics.referencia_mes')
            ->first();

        if (!$descriptiveData) {
            $descriptiveData = (object) [
                'materia_prima' => $commodity->nome,
                'volume_compra_ton' => 0, 'preco_medio_global' => 0,
                'preco_medio_brasil' => 0, 'preco_alvo' => 0, 'referencia_mes' => null,
            ];
        }

        // 4. Previsões Nacionais
        $nationalForecasts = DB::table('commodity_national_forecasts')
            ->select('referencia_mes', 'preco_medio', 'variacao_perc')
            ->where('commodity_id', $commodity->id)
            ->orderBy('referencia_mes')
            ->get()
            ->map(function ($forecast) {
                $forecast->mes_ano = $forecast->referencia_mes 
                    ? Str::ucfirst(Carbon::parse($forecast->referencia_mes)->locale('pt_BR')->translatedFormat('F/Y')) 
                    : '-';
                return $forecast;
            });

        // 5. Comparativos Regionais
        $regionalComparisons = DB::table('commodity_regional_comparisons')
            ->select('pais', 'preco_medio', 'logistica_perc', 'risco', 'estabilidade', 'ranking')
            ->where('commodity_id', $commodity->id)
            ->orderBy('ranking')
            ->get();

        return view('previ', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'descriptiveData' => $descriptiveData,
            'nationalForecasts' => $nationalForecasts,
            'regionalComparisons' => $regionalComparisons,
            'selectedCommodity' => $commodity, // AQUI GARANTIMOS O ID CORRETO NA VIEW
        ]);
    }

    /**
     * Tela de Gráficos
     */
    public function graficos(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        // Define o ID
        $commodityId = $id ?? $request->query('commodity_id');
        if (!$commodityId) {
            $commodityId = DB::table('commodities')->latest('id')->value('id');
        }

        // --- BUSCA DADOS REAIS DO BANCO PARA OS GRÁFICOS ---
        // Vamos usar a tabela de comparativos regionais para gerar os gráficos de barra
        $chartData = DB::table('commodity_regional_comparisons')
            ->select('pais', 'preco_medio', 'logistica_perc', 'risco', 'estabilidade')
            ->where('commodity_id', $commodityId)
            ->orderBy('preco_medio', 'desc') // Ordenar para ficar bonito no gráfico
            ->get();

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId,
            'chartData' => $chartData, // Passando os dados para a View
        ]);
    }

    /**
     * Tela de Conclusão
     */
    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        // Prioridade: ID da Rota -> ID da Query -> Último do Banco
        $commodityId = $id ?? $request->query('commodity_id');

        if (!$commodityId) {
            $commodityId = DB::table('commodities')->latest('id')->value('id');
        }

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId, 
        ]);
    }

    // --- MÉTODOS AUXILIARES ---

    private function getAuthenticatedUser(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return null;

        return DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin')
            ->where('id', $userId)
            ->first();
    }
}