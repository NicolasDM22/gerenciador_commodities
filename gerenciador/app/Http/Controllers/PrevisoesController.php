<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrevisoesController extends Controller
{
    public function index(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) {
            return redirect()->route('login');
        }
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodity = null;

        if ($id) {
            $commodity = DB::table('commodities')->where('id', $id)->first();
        }

        if (!$commodity && $request->query('commodity_id')) {
             $commodity = DB::table('commodities')->where('id', $request->query('commodity_id'))->first();
        }

        if (!$commodity) {
            $latestMetrics = DB::table('commodity_descriptive_metrics as metrics')
                ->select('metrics.commodity_id')
                ->orderByDesc('metrics.referencia_mes')
                ->orderByDesc('metrics.updated_at')
                ->orderByDesc('metrics.created_at')
                ->first();

            if ($latestMetrics) {
                $commodity = DB::table('commodities')->where('id', $latestMetrics->commodity_id)->first();
            }
        }

        if (!$commodity) {
            $commodity = DB::table('commodities')->orderBy('nome')->first();
        }

        if (!$commodity) {
            return redirect()->route('home')->withErrors('Nenhuma commodity cadastrada.');
        }

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
                'volume_compra_ton' => 0,
                'preco_medio_global' => 0,
                'preco_medio_brasil' => 0,
                'preco_alvo' => 0,
                'referencia_mes' => null,
            ];
        }

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
            'selectedCommodity' => $commodity,
        ]);
    }

    public function graficos(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

        $commodityId = $id ?? $request->query('commodity_id');
        if (!$commodityId) {
            $commodityId = DB::table('commodities')->latest('id')->value('id');
        }

        $chartData = DB::table('commodity_regional_comparisons')
            ->select('pais', 'preco_medio', 'logistica_perc', 'risco', 'estabilidade')
            ->where('commodity_id', $commodityId)
            ->orderBy('preco_medio', 'desc')
            ->get();

        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
            'commodityId' => $commodityId,
            'chartData' => $chartData,
        ]);
    }

    public function conclusao(Request $request, $id = null)
    {
        $user = $this->getAuthenticatedUser($request);
        if (!$user) return redirect()->route('login');
        $avatarUrl = $this->resolveAvatarUrl($user);

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

    public function exportarPdf($id)
    {
        $commodity = DB::table('commodities')->where('id', $id)->first();
        if(!$commodity) abort(404);

        $descriptiveData = DB::table('commodity_descriptive_metrics as metrics')
            ->select('metrics.*', 'commodities.nome as materia_prima')
            ->join('commodities', 'commodities.id', '=', 'metrics.commodity_id')
            ->where('metrics.commodity_id', $id)
            ->orderByDesc('metrics.referencia_mes')
            ->first();

        if (!$descriptiveData) {
            $descriptiveData = (object) [
                'materia_prima' => $commodity->nome,
                'volume_compra_ton' => 0,
                'preco_medio_global' => 0,
                'preco_medio_brasil' => 0,
                'preco_alvo' => 0,
            ];
        }

        $nationalForecasts = DB::table('commodity_national_forecasts')
            ->select('referencia_mes', 'preco_medio', 'variacao_perc')
            ->where('commodity_id', $id)
            ->orderBy('referencia_mes')
            ->get()
            ->map(function ($forecast) {
                $forecast->mes_ano = $forecast->referencia_mes 
                    ? Str::ucfirst(Carbon::parse($forecast->referencia_mes)->locale('pt_BR')->translatedFormat('F/Y')) 
                    : '-';
                return $forecast;
            });

        $regionalComparisons = DB::table('commodity_regional_comparisons')
            ->select('pais', 'preco_medio', 'logistica_perc', 'estabilidade', 'risco', 'ranking')
            ->where('commodity_id', $id)
            ->orderBy('ranking')
            ->get();

        $conclusionText = "Com base na análise de estabilidade econômica e climática, recomenda-se cautela nas negociações para os próximos trimestres. A volatilidade observada nos mercados emergentes sugere uma estratégia de hedging mais agressiva.";

        // Aqui está a alteração para chamar a view correta
        return view('pdfs.relatorio_completo', [
            'commodity'           => $commodity,
            'descriptiveData'     => $descriptiveData,
            'nationalForecasts'   => $nationalForecasts,
            'regionalComparisons' => $regionalComparisons,
            'conclusionText'      => $conclusionText,
            'date'                => date('d/m/Y H:i')
        ]);
    }

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