<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $commodityId = $request->query('commodity_id');

        $commodity = null;

        if ($commodityId) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->where('id', $commodityId)
                ->first();
        }

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

        if (!$commodity) {
            $commodity = DB::table('commodities')
                ->select('id', 'nome', 'categoria', 'unidade')
                ->orderBy('nome')
                ->first();
        }

        if (!$commodity) {
            return redirect()->route('home')->withErrors('Nenhuma commodity cadastrada para exibir.');
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
            ->orderByDesc('metrics.updated_at')
            ->orderByDesc('metrics.created_at')
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
                if ($forecast->referencia_mes) {
                    $forecast->mes_ano = Str::ucfirst(
                        Carbon::parse($forecast->referencia_mes)
                            ->locale('pt_BR')
                            ->translatedFormat('F/Y')
                    );
                } else {
                    $forecast->mes_ano = null;
                }

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

    public function graficos(Request $request)
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

        // TODO: Adicionar aqui a logica para buscar os dados especificos para os graficos
        return view('graficos', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
        ]);
    }

    public function conclusao(Request $request)
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

        // TODO: Adicionar aqui a logica para buscar os dados especificos para a conclusao

        return view('conclusao', [
            'user' => $user,
            'avatarUrl' => $avatarUrl,
        ]);
    }
}

