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
        // 1. Autenticação e Dados do Usuário
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodity = null;

        // 2. Lógica de Seleção da Commodity
        
        // Prioridade 1: ID passado na Rota (/previsoes/1)
        if ($id) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->where('id', $id)
                ->first();
        }

        // Prioridade 2: ID passado via Query String (/previsoes?commodity_id=1)
        if (!$commodity && $request->query('commodity_id')) {
             $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->where('id', $request->query('commodity_id'))
                ->first();
        }

        // Prioridade 3: Última commodity com métricas cadastradas (Modo Padrão)
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

        // Prioridade 4: Fallback (Primeira em ordem alfabética)
        if (!$commodity) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->orderBy('nome')
                ->first();
        }

        // Se ainda assim não achar nada, erro.
        if (!$commodity) {
            return redirect()->route('home')->withErrors('Nenhuma commodity cadastrada para exibir.');
        }
        }
        

        // 3. Buscar Dados Descritivos (Card Principal)
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
            ->orderByDesc('metrics.updated_at')
            ->orderByDesc('metrics.created_at')
            ->first();

        // Objeto vazio para evitar erros na View se não houver métricas
        if (!$descriptiveData) {
            $descriptiveData = (object) [
                'materia_prima' => $commodity->nome,
                'volume_compra_ton' => 0,
                'preco_medio_global' => 0,
                'preco_medio_brasil' => 0,
                'preco_alvo' => 0,
                'referencia_mes' => null,
            ];
        }

        // 4. Buscar Previsões Nacionais (Tabela 1)
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

        // 5. Buscar Comparativos Regionais (Tabela 2)
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
            'selectedCommodity' => $commodity, // Passamos o objeto completo para pegar o ID na View
        ]);
    }

    /**
     * Tela de Gráficos
     * Rota: /previsoes/graficos ou /previsoes/graficos/{id}
     */
    public function graficos(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        // Se o ID vier nulo, tentamos pegar da query string ou deixamos nulo (modo genérico)
        $commodityId = $id ?? $request->query('commodity_id');

        // TODO: Adicionar lógica para buscar dados reais do gráfico filtrando por $commodityId
        // Ex: $chartData = DB::table('commodity_charts')->where('commodity_id', $commodityId)->get();

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId, // Fundamental para os botões de navegação
        ]);
    }

    /**
     * Tela de Conclusão
     * Rota: /previsoes/conclusao ou /previsoes/conclusao/{id}
     */
    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodityId = $id ?? $request->query('commodity_id');

        // TODO: Adicionar lógica para buscar texto de conclusão e dados do gráfico final pelo $commodityId

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId, // Fundamental para os botões de navegação
        ]);
    }

    // --- MÉTODOS AUXILIARES PRIVADOS ---

    /**
     * Recupera o usuário autenticado na sessão personalizada
     */
    private function getAuthenticatedUser(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');

        if (!$userId) {
            return null;
        }

        return DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();
    }
}