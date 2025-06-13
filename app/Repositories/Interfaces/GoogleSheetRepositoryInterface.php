<?php

namespace App\Repositories\Interfaces;

use App\Models\GoogleSheet;

interface GoogleSheetRepositoryInterface
{
    public function findById($id);
    public function updateLastOrderSync(GoogleSheet $sheet, $lastOrderNumber = null);
    public function updateLastProductSync(GoogleSheet $sheet);
    public function trackProductSync(GoogleSheet $sheet);
    // public function update(GoogleSheet $sheet);
}