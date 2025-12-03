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
    // USADO COMO FONTE MESTRA DE NOMES (Mapeamento Hardcode)
    private $commodityMap = [
        1 => 'Soja',
        2 => 'Milho',
        3 => 'Açúcar',
        4 => 'Cacau',
    ];
    
    // Função auxiliar para verificar se a tabela existe
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

        $hasCommoditySaidaTable = $this->tableExists('commodity_saida');
        
        // --- 1. DATATABLES (Análises Anteriores) ---
        
        $analysisQuery = DB::table('commodity_saida')
            ->where('tipo_analise', 'PREVISAO_MENSAL')
            ->orderByDesc('referencia_mes')
            ->selectRaw('commodity_saida.id, commodity_saida.commodity_id, commodity_saida.referencia_mes, commodity_saida.created_at, commodity_saida.updated_at');

        try {
            $analysis = $analysisQuery->get()
                ->map(function ($item) {
                    $item->commodity_nome = $this->commodityMap[$item->commodity_id] ?? 'ID Desconhecido: ' . $item->commodity_id;
                    $item->data_previsao = $item->referencia_mes ? Carbon::parse($item->referencia_mes)->format('d/m/Y') : '-';
                    return $item;
                });
        } catch (\Exception $e) {
            $analysis = collect();
        }


        // --- 2. GRÁFICO: Histórico/Forecast (Baseado no MAIOR ID de Análise) ---
        
        $defaultCommodityId = array_key_first($this->commodityMap) ?? 1; // Fallback para ID 1
        
        if ($hasCommoditySaidaTable) {
            try {
                // Seleciona o registro de maior PK 'id' que seja do tipo PREVISAO_MENSAL
                $maxIdAnalysis = DB::table('commodity_saida')
                    ->where('tipo_analise', 'PREVISAO_MENSAL')
                    ->orderByDesc('id') 
                    ->select('commodity_id', 'referencia_mes')
                    ->first();

                if ($maxIdAnalysis) {
                    // Define o ID da commodity a partir do registro mais recente
                    $defaultCommodityId = $maxIdAnalysis->commodity_id;
                }
            } catch (\Exception $e) {
                // Falha silenciosamente, usando o ID 1
            }
        }
        
        $commodityName = $this->commodityMap[$defaultCommodityId] ?? 'Histórico Geral';
        
        $chartData = ['labels' => [], 'prices' => [], 'commodityName' => $commodityName];

        if ($hasCommoditySaidaTable) {
            try {
                // Puxa a análise mais recente para o commodity ID determinado acima
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
                        'commodityName' => $commodityName
                    ];
                }
            } catch (\Exception $e) {
                // Ignora falha de leitura
            }
        }
        
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