<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Services\AuditLogger;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportController extends BaseController
{
    public function __construct(private ExportService $exportService) {}

    /**
     * GET /export/{module}?format=xlsx|csv|pdf&...filters
     */
    public function export(Request $request, string $module): mixed
    {
        $format = strtolower($request->get('format', 'xlsx'));

        if (!in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return $this->sendError('Format invalide. Valeurs acceptées : xlsx, csv, pdf.', [], 422);
        }

        try {
            $params = $request->except(['format']);
            $data   = $this->exportService->exporter($module, $params);
        } catch (\InvalidArgumentException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la génération de l\'export : ' . $e->getMessage(), [], 500);
        }

        $slug     = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $data['titre']));
        $date     = now()->format('Y-m-d');
        $filename = "ebeb_{$slug}_{$date}.{$format}";

        AuditLogger::log(
            'EXPORT.' . strtoupper(str_replace('-', '_', $module)),
            $request->user(),
            'export',
            null,
            null,
            ['format' => $format, 'nb_lignes' => count($data['rows']), 'fichier' => $filename]
        );

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.generic', [
                'titre'    => $data['titre'],
                'headings' => $data['headings'],
                'rows'     => $data['rows'],
                'date'     => now()->format('d/m/Y H:i'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename);
        }

        $exportClass = new \App\Exports\GenericExport($data['titre'], $data['headings'], $data['rows']);
        $writerType  = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return Excel::download($exportClass, $filename, $writerType);
    }
}
