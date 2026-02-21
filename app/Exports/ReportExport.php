<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Collection;

class ReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $reportType;

    protected $columnConfig = [
        'delivery' => [
            'Order No',
            'Product Items',
            'Receiver Name',
            'Receiver Address',
            'Receiver Phone',
            'Receiver Town',
            'Cash on Delivery',
            'Total Qty',
            'Order Status',
            'Created At',
            'Scheduled Date',
            'Delivery Date',
            'Rider',
            'Zone'
        ],
        'merchant' => [
            'Order No',
            'Product Items',
            'Receiver Name',
            'Receiver Address',
            'Receiver Phone',
            'Receiver Town',
            'Special Instructions',
            'Cash on Delivery',
            'Total Qty',
            'Order Status',
            'Created At',
            'Scheduled Date',
            'Delivery Date',
            'Rider',
            'Zone',
            'Agent'
        ],
        'default' => [
            'Order No',
            'Product Items',
            'Receiver Name',
            'Receiver Phone',
            'Order Status',
            'Created At',
            'Zone'
        ]
    ];

    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    /**
     * Return collection of data
     */
    public function collection()
    {
        return new Collection($this->data);
    }

    /**
     * Define headings based on report type
     */
    public function headings(): array
    {
        return $this->columnConfig[$this->reportType] ?? $this->columnConfig['default'];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        // Header row styling
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'] // Blue-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);

        // Auto-height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Zebra striping for data rows
        $lastRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':' . $sheet->getHighestColumn() . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('F3F4F6'); // Gray-100
            }
        }

        // Border for all cells
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'], // Gray-300
                ],
            ],
        ]);

        return [];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15,  // Order No
            'B' => 40,  // Product Items
            'C' => 25,  // Receiver Name
            'D' => 35,  // Receiver Address
            'E' => 18,  // Receiver Phone
            'F' => 20,  // Receiver Town
            'G' => 25,  // Special Instructions / COD
            'H' => 18,  // Cash on Delivery / Total Qty
            'I' => 12,  // Total Qty / Order Status
            'J' => 20,  // Order Status / Created At
            'K' => 20,  // Created At
            'L' => 18,  // Scheduled Date
            'M' => 18,  // Delivery Date
            'N' => 20,  // Rider
            'O' => 18,  // Zone
            'P' => 20,  // Agent (for merchant report)
        ];
    }

    /**
     * Define sheet title
     */
    public function title(): string
    {
        return ucfirst($this->reportType) . ' Report';
    }
}
