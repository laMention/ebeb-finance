<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class AuditSecuriteExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn($row) => array_values((array) $row));
    }

    public function headings(): array
    {
        return [
            'Code', 'Titre', 'Criticité', 'Statut', 'Catégorie',
            'Description', 'Impact', 'Recommandation', 'Fichier', 'Ligne',
            'Détecté le', 'Corrigé le', 'Notes correction',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'    => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A3A5C']],
            ],
        ];
    }
}
