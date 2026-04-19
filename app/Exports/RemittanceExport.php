<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RemittanceExport implements FromView, WithTitle, ShouldAutoSize
{
    public function __construct(
        protected $remittance,
        protected array $serviceColumns,
        protected array $company,
        protected string $startDate,
        protected string $endDate,
    ) {}

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

    public function title(): string
    {
        return 'Remittance Report';
    }
}
