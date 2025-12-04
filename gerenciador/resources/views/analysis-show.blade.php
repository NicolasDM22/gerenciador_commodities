<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Análise</title>
    <style>
        :root {
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-700: #374151;
            --gray-900: #111827;
            --white: #ffffff;
            --primary: #2563eb;
        }
        body { margin:0; font-family:"Segoe UI", Arial, sans-serif; background: var(--gray-100); color: var(--gray-900); }
        .page { min-height:100vh; display:flex; flex-direction:column; }
        main.content { flex:1; width:min(1100px,100%); margin:0 auto; padding:2rem; display:grid; gap:1.5rem; }
        .card { background:var(--white); border-radius:20px; padding:1.5rem; box-shadow:0 12px 35px rgba(15,23,42,0.12); }
        .card h2 { margin-top:0; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; }
        .pill { background:var(--gray-50); border-radius:16px; padding:0.75rem 1rem; border:1px solid var(--gray-200); }
        .table { width:100%; border-collapse:collapse; }
        .table th,.table td { padding:0.65rem 0.8rem; border-bottom:1px solid var(--gray-200); text-align:left; }
        .button { border-radius:12px; padding:0.55rem 1.2rem; border:1px solid var(--primary); color:var(--primary); text-decoration:none; font-weight:600; background:transparent; }
    </style>
</head>
<body>
<div class="page">
    <x-topbar :user="$user" :isAdmin="$isAdmin ?? false">
        <a href="{{ route('home') }}" class="button">Voltar ao painel</a>
    </x-topbar>

    <main class="content">
        <div class="card">
            <h2>Resumo da análise</h2>
            <div class="grid">
                <div class="pill">
                    <strong>Matéria-prima</strong><br>
                    {{ $analysis->materia_prima ?? '-' }}
                </div>
                <div class="pill">
                    <strong>Volume</strong><br>
                    {{ number_format($analysis->volume_kg ?? 0, 2, ',', '.') }} kg
                </div>
                <div class="pill">
                    <strong>Preço alvo</strong><br>
                    R$ {{ number_format($analysis->preco_alvo ?? 0, 2, ',', '.') }}
                </div>
                <div class="pill">
                    <strong>Registrada em</strong><br>
                    {{ $created_at }}
                </div>
            </div>
        </div>

        @if(!empty($mercados))
        <div class="card">
            <h2>Mercados avaliados</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Destino</th>
                        <th>Preço</th>
                        <th>Prazo estimado</th>
                        <th>Justificativa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mercados as $mercado)
                        <tr>
                            <td>{{ $mercado['nome'] ?? '-' }}</td>
                            <td>{{ ($mercado['moeda'] ?? 'BRL') }} {{ number_format($mercado['preco'] ?? 0, 2, ',', '.') }}</td>
                            <td>{{ $mercado['prazo_estimado_dias'] ?? 0 }} dias</td>
                            <td>{{ $mercado['justificativa'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="card">
            <h2>Indicadores</h2>
            <div class="grid">
                <div class="pill">
                    <strong>Média Brasil</strong><br>
                    R$ {{ number_format($indicadores['media_brasil'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="pill">
                    <strong>Média Global</strong><br>
                    R$ {{ number_format($indicadores['media_global'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="pill">
                    <strong>Risco</strong><br>
                    {{ $indicadores['risco'] ?? '-' }}
                </div>
                <div class="pill">
                    <strong>Estabilidade</strong><br>
                    {{ $indicadores['estabilidade'] ?? '-' }}
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Logística</h2>
            <div class="grid">
                <div class="pill">
                    <strong>Melhor rota</strong><br>
                    {{ $logistica['melhor_rota'] ?? '-' }}
                </div>
                <div class="pill">
                    <strong>Custo estimado</strong><br>
                    R$ {{ number_format($logistica['custo_estimado'] ?? 0, 2, ',', '.') }}
                </div>
                <div class="pill">
                    <strong>Observações</strong><br>
                    {{ $logistica['observacoes'] ?? '-' }}
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Recomendação final</h2>
            <p>{{ $recomendacao ?? 'Não informado.' }}</p>
        </div>
    </main>
</div>
</body>
</html>
