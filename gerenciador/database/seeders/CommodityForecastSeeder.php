<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommodityForecastSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            $commodity = DB::table('commodities')
                ->where('nome', 'Cacau (Tipo Forasteiro)')
                ->first();

            if ($commodity) {
                $commodityId = $commodity->id;
                DB::table('commodities')
                    ->where('id', $commodityId)
                    ->update([
                        'categoria' => $commodity->categoria ?? 'Agricola',
                        'unidade' => $commodity->unidade ?? 'kg',
                        'updated_at' => $now,
                    ]);
            } else {
                $commodityId = DB::table('commodities')->insertGetId([
                    'nome' => 'Cacau (Tipo Forasteiro)',
                    'categoria' => 'Agricola',
                    'unidade' => 'kg',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('commodity_descriptive_metrics')->updateOrInsert(
                [
                    'commodity_id' => $commodityId,
                    'referencia_mes' => '2026-01-01',
                ],
                [
                    'volume_compra_ton' => 10.00,
                    'preco_medio_global' => 60.00,
                    'preco_medio_brasil' => 43.50,
                    'preco_alvo' => 35.00,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            $forecasts = [
                ['2026-01-01', 60.00, -10.00],
                ['2026-02-01', 63.00, 5.00],
                ['2026-03-01', 56.00, -11.11],
                ['2026-04-01', 52.00, -7.14],
            ];

            foreach ($forecasts as [$reference, $price, $variation]) {
                DB::table('commodity_national_forecasts')->updateOrInsert(
                    [
                        'commodity_id' => $commodityId,
                        'referencia_mes' => $reference,
                    ],
                    [
                        'preco_medio' => $price,
                        'variacao_perc' => $variation,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $regions = [
                ['Brasil', 17.80, 6.00, 'Medio (Chuvas)', 'Alta', 1],
                ['Indonesia', 15.40, 18.00, 'Alto (Alta umidade)', 'Media', 3],
                ['Costa do Marfim', 14.90, 12.00, 'Alto (Instabilidade)', 'Baixa', 2],
            ];

            foreach ($regions as [$country, $price, $logistics, $risk, $stability, $ranking]) {
                DB::table('commodity_regional_comparisons')->updateOrInsert(
                    [
                        'commodity_id' => $commodityId,
                        'pais' => $country,
                    ],
                    [
                        'preco_medio' => $price,
                        'logistica_perc' => $logistics,
                        'risco' => $risk,
                        'estabilidade' => $stability,
                        'ranking' => $ranking,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });
    }
}

