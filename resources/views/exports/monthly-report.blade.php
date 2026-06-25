<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório FlowFin — {{ $report['month_label'] }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; }
        h1 { color: #2563eb; font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6b7280; font-size: 11px; }
        .totais { width: 100%; margin: 16px 0; border-collapse: collapse; }
        .totais td { padding: 6px 8px; }
        .card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
        table.tx { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.tx th { background: #2563eb; color: #fff; text-align: left; padding: 6px; font-size: 11px; }
        table.tx td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; }
        .entrada { color: #16a34a; }
        .saida { color: #dc2626; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Relatório FlowFin</h1>
    <div class="muted">{{ ucfirst($report['month_label']) }} — {{ $report['user_name'] }}</div>
    <div class="muted">Gerado em {{ $report['generated_at'] }}</div>

    <table class="totais">
        <tr>
            <td class="card"><strong>Entrou</strong><br>R$ {{ \App\Support\Money::format($report['totals']['entrou']) }}</td>
            <td class="card"><strong>Saiu</strong><br>R$ {{ \App\Support\Money::format($report['totals']['saiu']) }}</td>
            <td class="card"><strong>Sobrou</strong><br>R$ {{ \App\Support\Money::format($report['totals']['sobrou']) }}</td>
        </tr>
    </table>

    <table class="tx">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipo</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Classificação</th>
                <th class="right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td class="{{ $row['type'] === 'Entrada' ? 'entrada' : 'saida' }}">{{ $row['type'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['classification'] }}</td>
                    <td class="right">R$ {{ \App\Support\Money::format($row['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Nenhuma movimentação neste mês.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
