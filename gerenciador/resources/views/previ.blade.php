<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previsão de Commodities - Descritiva</title>
    <style>
        /* ESTILOS GERAIS */
        :root {
            --gray-50: #f9fafb; --gray-100: #f3f4f6; --gray-200: #e5e7eb;
            --gray-300: #d1d5db; --gray-500: #6b7280; --gray-600: #4b5563;
            --gray-700: #374151; --gray-900: #111827;
            --primary: #2563eb; --primary-dark: #1d4ed8;
            --success: #059669; --danger: #dc2626; --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--gray-100); font-family: "Segoe UI", Arial, sans-serif; color: var(--gray-900); height: 100vh; overflow: hidden; }
        .page { height: 100%; display: flex; flex-direction: column; }
        .page > header,
        .page > x-topbar {
            flex-shrink: 0;
        }

        /* HEADER & BOTOES */
        .button { border: none; border-radius: 12px; padding: 0.75rem 1.4rem; font-size: 0.95rem; font-weight: 600; cursor: pointer; transition: 0.15s; display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem; text-decoration: none; }
        .button:hover { transform: translateY(-1px); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.18); }
        .button-secondary { background: var(--white); border: 1px solid var(--gray-300); color: var(--gray-700); }
        .button-secondary:hover { background: var(--gray-50); }
        .button[disabled] { opacity: 0.55; cursor: not-allowed; }
        .button-icon { padding: 0.6rem 0.8rem; line-height: 1; }

        /* LAYOUT */
        main.content { flex: 1; width: min(1280px, 100%); margin: 0 auto; padding: 2rem clamp(1rem, 2vw, 2.5rem) 3rem; display: flex; flex-direction: column; overflow: hidden; gap: 1.75rem; }
        .card { background: var(--white); border-radius: 22px; padding: 1.5rem; box-shadow: 0 22px 45px -30px rgba(15, 23, 42, 0.3); display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        
        .analysis-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-bottom: 1.25rem; margin-bottom: 1rem; border-bottom: 1px solid var(--gray-200); flex-shrink: 0; }
        .analysis-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--gray-600); }
        .nav-buttons { display: flex; gap: 0.5rem; }

        .analysis-body { display: flex; flex-direction: column; gap: 1.5rem; overflow-y: auto; flex: 1; padding-right: 1rem; }
        .analysis-body::-webkit-scrollbar { width: 8px; }
        .analysis-body::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }

        .analysis-section h3 { margin-bottom: 1.25rem; font-size: 1.15rem; font-weight: 600; color: var(--gray-700); }

        /* GRIDS & TABLE */
        .descriptive-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem 2rem; }
        .descriptive-grid p { margin: 0.6rem 0; font-size: 0.95rem; color: var(--gray-700); display: flex; justify-content: space-between; border-bottom: 1px dashed #f0f0f0; padding-bottom: 5px; }
        .descriptive-grid strong { font-weight: 600; color: var(--gray-600); }
        .descriptive-grid span { font-weight: 600; color: var(--gray-900); }
        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }

        .table-wrapper { overflow: auto; border-radius: 12px; border: 1px solid var(--gray-200); }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 0.85rem 1rem; text-align: left; font-size: 0.9rem; border-bottom: 1px solid var(--gray-200); }
        th { background: var(--gray-50); font-weight: 600; color: var(--gray-700); text-transform: uppercase; font-size: 0.8rem; }
        tr:last-child td { border-bottom: none; }
        .text-center { text-align: center; }
        
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; }
        .badge-good { background: #d1fae5; color: #065f46; }
        .badge-mid { background: #ffedd5; color: #9a3412; }
        .badge-bad { background: #fee2e2; color: #991b1b; }
        /* TOP BAR */
        .top-bar { 
            background: var(--white); 
            padding: 1.5rem clamp(1.5rem, 3vw, 3rem); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            gap: 1.5rem; 
            box-shadow: 0 4px 22px rgba(15, 23, 42, 0.08); 
            flex-shrink: 0;
        }
        .profile { 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
        }
        .avatar { 
            width: 64px; 
            height: 64px; 
            border-radius: 18px; 
            object-fit: cover; 
            border: 3px solid var(--gray-200); 
        }
        .profile-info strong { 
            font-size: 1.25rem; 
            display: block; 
        }
        .profile-info span { 
            color: var(--gray-500); 
            font-size: 0.95rem; 
        }
        .top-actions { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 0.75rem; 
            align-items: center; 
        }
    </style>
</head>
<body>
<div class="page">
    <x-topbar :user="$user">
    </x-topbar>

    <main class="content">
        @if (session('status'))
            <div style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1rem;">
                {{ session('status') }}
            </div>
        @endif

        <section class="card">
            <div class="analysis-header">
                <div class="nav-buttons">
                    <button class="button button-secondary button-icon" disabled>&larr;</button>
                    <a href="{{ route('previsoes.graficos.show', ['id' => $analysisId ?? $selectedCommodity->id]) }}" class="button button-secondary button-icon" title="Ir para Gráficos">&rarr;</a>
                </div>
                <h2>Análise Descritiva - {{ $selectedCommodity->nome }}</h2>
                <a href="{{ route('home') }}" class="button button-secondary button-icon" style="font-size: 1.2rem; line-height: 0.8;" title="Voltar">&times;</a>
            </div>
            
            <div class="analysis-body">
                <div class="analysis-section">
                    <div class="descriptive-grid">
                        @php $variacao = $descriptiveData->preco_medio_brasil - $descriptiveData->preco_alvo; @endphp
                        <div>
                            <p><strong>Matéria Prima:</strong> <span>{{ $descriptiveData->materia_prima }}</span></p>
                            <p><strong>Mês de Referência:</strong> <span>{{ $descriptiveData->referencia_mes ? date('m/Y', strtotime($descriptiveData->referencia_mes)) : 'N/A' }}</span></p>
                            <p><strong>Volume Compra:</strong> <span>{{ number_format($descriptiveData->volume_compra_ton, 0, ',', '.') }} Ton</span></p>
                        </div>
                        <div>
                            <p><strong>Preço Brasil (Atual):</strong> <span>R$ {{ number_format($descriptiveData->preco_medio_brasil, 2, ',', '.') }}</span></p>
                            <p><strong>Preço Alvo:</strong> <span>R$ {{ number_format($descriptiveData->preco_alvo, 2, ',', '.') }}</span></p>
                            <p><strong>Gap / Variação:</strong> <span class="{{ $variacao > 0 ? 'text-danger' : 'text-success' }}">R$ {{ number_format($variacao, 2, ',', '.') }}</span></p>
                        </div>
                    </div>
                </div>
                
                {{-- SEÇÃO "MERCADOS RECOMENDADOS PELA IA" (CARDS) REMOVIDA --}}

                <div class="analysis-section">
                    <h3>Tendência do Mercado Nacional (Projeção)</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mês/Ano</th>
                                    <th>Preço Projetado (R$/kg)</th>
                                    <th>Variação Mensal (%)</th>
                                    <th class="text-center">Tendência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($nationalForecasts as $forecast)
                                    <tr>
                                        <td>{{ $forecast->mes_ano }}</td>
                                        <td>R$ {{ number_format($forecast->preco_medio, 2, ',', '.') }}</td>
                                        <td class="{{ $forecast->variacao_perc >= 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $forecast->variacao_perc > 0 ? '+' : '' }}{{ number_format($forecast->variacao_perc, 2, ',', '.') }}%
                                        </td>
                                        <td class="text-center">
                                            @if($forecast->variacao_perc > 0) <span style="color:#dc2626">▲ Alta</span>
                                            @elseif($forecast->variacao_perc < 0) <span style="color:#059669">▼ Baixa</span>
                                            @else <span style="color:#6b7280">➖ Estável</span> @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">Nenhuma previsão calculada.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- NOVA TABELA: COMPARATIVO REGIONAL (BASEADO NA ESCOLHA DA IA) --}}
                <div class="analysis-section">
                    <h3>Comparativo Regional (Top 3 Selecionados pela IA)</h3>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Localização Recomendada</th>
                                    <th>Preço Estimado</th>
                                    <th>Logística / Obs</th>
                                    <th>Estabilidade Econômica</th>
                                    <th>Estabilidade Climática</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Usamos $regionalComparisons que contém os dados escolhidos e enriquecidos pela IA --}}
                                @forelse ($regionalComparisons ?? [] as $market)
                                    <tr>
                                        <td><strong>{{ $market->pais }}</strong></td>
                                        <td>{{ $market->moeda }} {{ number_format($market->preco_medio, 2, ',', '.') }}</td>
                                        <td style="font-size: 0.85rem;">{{ $market->logistica_obs }}</td>
                                        <td>
                                            @php 
                                                $eco = mb_strtolower($market->estabilidade_economica);
                                                $classEco = str_contains($eco, 'alta') ? 'badge-good' : (str_contains($eco, 'baixa') ? 'badge-bad' : 'badge-mid');
                                            @endphp
                                            <span class="badge {{ $classEco }}">{{ $market->estabilidade_economica }}</span>
                                        </td>
                                        <td>
                                            @php 
                                                $clim = mb_strtolower($market->estabilidade_climatica);
                                                $classClim = str_contains($clim, 'alta') ? 'badge-good' : (str_contains($clim, 'baixa') ? 'badge-bad' : 'badge-mid');
                                            @endphp
                                            <span class="badge {{ $classClim }}">{{ $market->estabilidade_climatica }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhuma recomendação disponível. Gere uma nova análise.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div> 
        </section>
    </main>
</div>
</body>
</html>