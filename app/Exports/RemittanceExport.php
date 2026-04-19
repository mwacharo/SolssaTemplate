<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class RemittanceExport implements FromView, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected $remittance,
        protected array $serviceColumns,
        protected array $company,
        protected string $startDate,
        protected string $endDate,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // VIEW
    // Points to resources/views/remittance/excelreport.blade.php
    // Row 1 is reserved as an empty tall row when a logo exists — the drawing
    // is placed on top of it via WithEvents below.
    // ─────────────────────────────────────────────────────────────────────────
    public function view(): View
    {
        return view('remittance.excelreport', [
            'remittance'     => $this->remittance,
            'startDate'      => $this->startDate,
            'endDate'        => $this->endDate,
            'serviceColumns' => $this->serviceColumns,
            'company'        => $this->company,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EVENTS — inject logo image after the sheet is built
    //
    // <img> tags are ignored by Maatwebsite's HTML parser, so the only way to
    // get a logo into the sheet is via a PhpSpreadsheet Drawing attached in
    // the AfterSheet event. The blade reserves row 1 as a tall empty row when
    // $company['logo'] is set, and the drawing is anchored to cell A1.
    // ─────────────────────────────────────────────────────────────────────────
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $logoPath = $this->company['logo'] ?? null;

                if (empty($logoPath)) {
                    return;
                }

                // Resolve absolute path — logo_path may be stored as
                // "logos/filename.png"  (relative to storage/app/public)
                // or as a full absolute path already.
                $absolutePath = file_exists($logoPath)
                    ? $logoPath
                    : public_path('storage/' . ltrim($logoPath, '/'));

                if (! file_exists($absolutePath)) {
                    return; // skip silently if file missing
                }

                $sheet = $event->sheet->getDelegate();

                // Set row 1 tall enough to display the logo
                $sheet->getRowDimension(1)->setRowHeight(60);

                $drawing = new Drawing();
                $drawing->setName('Company Logo');
                $drawing->setDescription('Company Logo');
                $drawing->setPath($absolutePath);
                $drawing->setHeight(55);           // px — fits inside the 60pt row
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(3);
                $drawing->setWorksheet($sheet);
            },
        ];
    }

    public function title(): string
    {
        return 'Remittance Report';
    }
}
