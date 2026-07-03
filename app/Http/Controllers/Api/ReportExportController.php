<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DataExportService;
use App\Services\MonthlyReportService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export de relatórios e dos dados do usuário.
 *
 * - Relatório mensal em CSV e PDF (R$ no formato brasileiro, datas dd/mm/aaaa, PT-BR).
 * - Export completo (LGPD) em JSON com todas as entidades do usuário.
 *
 * Tudo escopado ao usuário autenticado (`$request->user()`).
 *
 * Contratos (UI 5.4):
 *   GET /api/export/monthly?month=aaaa-mm&format=csv|pdf
 *     → download do arquivo ("relatorio-aaaa-mm.csv" | ".pdf"). Default format=csv.
 *       CSV: separador ";" (padrão do Excel pt-BR), BOM UTF-8, valores "R$ 1.234,56".
 *   GET /api/export/full
 *     → download JSON ("flowfin-dados-<id>.json"); valores em CENTAVOS (inteiro).
 */
class ReportExportController extends Controller
{
    public function monthly(Request $request, MonthlyReportService $reports): Response
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'format' => ['nullable', 'in:csv,pdf'],
        ]);

        $report = $reports->build($request->user(), $validated['month'] ?? null);
        $format = $validated['format'] ?? 'csv';
        $filename = "relatorio-{$report['month']}";

        return $format === 'pdf'
            ? $this->pdf($report, $filename)
            : $this->csv($report, $filename);
    }

    public function full(Request $request, DataExportService $export): StreamedResponse
    {
        $data = $export->build($request->user());
        $filename = 'flowfin-dados-'.$request->user()->id.'.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function csv(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 para que o Excel pt-BR exiba acentos corretamente.
            fwrite($out, "\xEF\xBB\xBF");

            // Cabeçalho de resumo.
            fputcsv($out, ['Relatório FlowFin', $report['month_label']], ';');
            fputcsv($out, ['Entrou', Money::formatBRL($report['totals']['entrou'])], ';');
            fputcsv($out, ['Saiu', Money::formatBRL($report['totals']['saiu'])], ';');
            fputcsv($out, ['Sobrou', Money::formatBRL($report['totals']['sobrou'])], ';');
            fputcsv($out, [], ';');

            // Linhas das transações.
            fputcsv($out, ['Data', 'Tipo', 'Categoria', 'Descrição', 'Classificação', 'Valor'], ';');
            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['date'],
                    $row['type'],
                    $row['category'],
                    $row['description'],
                    $row['classification'],
                    Money::formatBRL($row['amount']),
                ], ';');
            }

            fclose($out);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function pdf(array $report, string $filename): Response
    {
        $pdf = Pdf::loadView('exports.monthly-report', ['report' => $report]);

        return $pdf->download("{$filename}.pdf");
    }
}
