<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    private $commodityMap = [
        1 => 'Soja',
        2 => 'Milho',
        3 => 'Açúcar',
        4 => 'Cacau',
    ];

    private function tableExists($tableName)
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('auth_user_id');
        if (!$userId) return redirect()->route('login');

        $user = DB::table('users')
            ->select('id', 'usuario', 'nome', 'email', 'telefone', 'endereco', 'foto_blob', 'foto_mime', 'is_admin', 'created_at', 'updated_at')
            ->where('id', $userId)
            ->first();

        if (!$user) return redirect()->route('login');

        $isAdmin = (bool) ($user->is_admin ?? false);
        $avatarUrl = $this->resolveAvatarUrl($user);
        $hasCommoditySaidaTable = $this->tableExists('commodity_saida');

        $analysis = collect();
        if ($hasCommoditySaidaTable) {
            try {
                $analysis = DB::table('commodity_saida')
                    ->where('tipo_analise', 'PREVISAO_MENSAL')
                    ->orderByDesc('updated_at')
                    ->select('id', 'commodity_id', 'referencia_mes', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($item) {
                        $item->commodity_nome = $this->commodityMap[$item->commodity_id] ?? 'Commodity #' . $item->commodity_id;
                        $dataBase = $item->updated_at ?? $item->created_at ?? now();
                        $item->data_previsao = Carbon::parse($dataBase)->format('d/m/Y H:i');
                        return $item;
                    });
            } catch (\Exception $e) {
                $analysis = collect();
            }
        }

        $defaultCommodityId = array_key_first($this->commodityMap) ?? 1;
        if ($hasCommoditySaidaTable) {
            try {
                $maxIdAnalysis = DB::table('commodity_saida')
                    ->where('tipo_analise', 'PREVISAO_MENSAL')
                    ->orderByDesc('id')
                    ->select('commodity_id', 'referencia_mes')
                    ->first();

                if ($maxIdAnalysis) {
                    $defaultCommodityId = $maxIdAnalysis->commodity_id;
                }
            } catch (\Exception $e) {
            }
        }

        $commodityName = $this->commodityMap[$defaultCommodityId] ?? 'Histórico Geral';
        $chartData = ['labels' => [], 'prices' => [], 'commodityName' => $commodityName];

        if ($hasCommoditySaidaTable) {
            try {
                $analysisData = DB::table('commodity_saida')
                    ->where('commodity_id', $defaultCommodityId)
                    ->where('tipo_analise', 'PREVISAO_MENSAL')
                    ->select(
                        'referencia_mes',
                        'preco_3_meses_anterior',
                        'preco_2_meses_anterior',
                        'preco_1_mes_anterior',
                        'preco_mes_atual',
                        'preco_1_mes_depois',
                        'preco_2_meses_depois',
                        'preco_3_meses_depois',
                        'preco_4_meses_depois'
                    )
                    ->orderByDesc('referencia_mes')
                    ->first();

                if ($analysisData) {
                    $pricePoints = [
                        $analysisData->preco_3_meses_anterior,
                        $analysisData->preco_2_meses_anterior,
                        $analysisData->preco_1_mes_anterior,
                        $analysisData->preco_mes_atual,
                        $analysisData->preco_1_mes_depois,
                        $analysisData->preco_2_meses_depois,
                        $analysisData->preco_3_meses_depois,
                        $analysisData->preco_4_meses_depois,
                    ];

                    $refDate = Carbon::parse($analysisData->referencia_mes);
                    $chartLabels = [];
                    for ($i = -3; $i <= 4; $i++) {
                        $chartLabels[] = $refDate->copy()->addMonths($i)->format('M/y');
                    }

                    $chartData = [
                        'labels' => $chartLabels,
                        'prices' => array_map(fn($p) => $p ?? 0, $pricePoints),
                        'commodityName' => $commodityName,
                    ];
                }
            } catch (\Exception $e) {
            }
        }

        $adminData = ['notifications' => collect()];
        if ($isAdmin) {
            $adminData['notifications'] = DB::table('admin_notifications')
                ->select('id', 'title', 'body', 'status', 'created_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(function ($notification) {
                    $createdAt = $notification->created_at ? Carbon::parse($notification->created_at) : null;
                    $notification->created_at_formatted = $createdAt ? $createdAt->format('d/m/Y H:i') : 'Não informado';
                    return $notification;
                });
        }

        return view('home', [
            'user' => $user,
            'avatarUrl' => $this->resolveAvatarUrl($user),
            'chartData' => $chartData,
            'isAdmin' => $isAdmin,
            'adminData' => $adminData,
            'previousAnalyses' => $analysis,
            'aiAnalyses' => $this->latestAiAnalyses($userId, $isAdmin),
        ]);
    }

    private function latestAiAnalyses(int $userId, bool $isAdmin)
    {
        $query = DB::table('ai_analysis_logs')->orderByDesc('created_at');
        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        return $query->limit(5)
            ->get()
            ->map(function ($item) {
                $item->parsed = json_decode($item->response ?? '', true) ?: null;
                $item->created_at_formatted = $item->created_at ? Carbon::parse($item->created_at)->format('d/m/Y H:i') : '';
                return $item;
            });
    }
}