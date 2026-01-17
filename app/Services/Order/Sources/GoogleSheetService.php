<?php

namespace App\Services\Order\Sources;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetService
{
    protected $client;
    public $sheetsService;
    protected $spreadsheetId;

    /**
     * Constructor to initialize the Google API client
     */
    public function __construct()
    {
        try {
            $this->client = new Client();

            // Check if credentials file exists
            $credentialsPath = storage_path('credentials/credentials.json');
            if (!file_exists($credentialsPath)) {
                Log::error('Google API credentials file not found at: ' . $credentialsPath);
                throw new \Exception('Google API credentials file not found');
            }

            $this->client->setAuthConfig($credentialsPath);
            $this->client->addScope('https://www.googleapis.com/auth/spreadsheets');
            $this->client->setAccessType('offline');
            $this->client->setApplicationName('CustomerServiceSupport');

            // Create Sheets service
            $this->sheetsService = new Sheets($this->client);

            Log::info('Google Sheets service initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Sheets service: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Set the current spreadsheet ID for operations
     *
     * @param string $spreadsheetId
     * @return $this
     */
    public function setSpreadsheetId(string $spreadsheetId)
    {
        $this->spreadsheetId = $spreadsheetId;
        Log::info('Set spreadsheet ID: ' . $spreadsheetId);
        return $this;
    }

    /**
     * Get sheet data from Google Sheets
     *
     * @param string $range The range of cells to fetch (e.g. "Sheet1!A1:D10")
     * @return array
     */
    public function getSheetData(string $range)
    {
        try {
            if (empty($this->spreadsheetId)) {
                Log::error('Spreadsheet ID not set before calling getSheetData');
                throw new \Exception('Spreadsheet ID not set');
            }

            Log::info('Fetching sheet data for range: ' . $range);
            $response = $this->sheetsService->spreadsheets_values->get(
                $this->spreadsheetId,
                $range
            );

            $values = $response->getValues();
            Log::info('Fetched ' . (is_array($values) ? count($values) : 0) . ' rows from Google Sheets');
            return $values ?: [];
        } catch (\Exception $e) {
            Log::error('Google Sheets API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Read all data from a specific sheet in the Google Spreadsheet.
     *
     * @param string $sheetName The name of the sheet to read (e.g. "Sheet1")
     * @return array
     */
    public function readAllSheetData(string $sheetName)
    {
        try {
            if (empty($this->spreadsheetId)) {
                Log::error('Spreadsheet ID not set before calling readAllSheetData');
                throw new \Exception('Spreadsheet ID not set');
            }

            Log::info('Reading all data from sheet: ' . $sheetName);
            $response = $this->sheetsService->spreadsheets_values->get(
                $this->spreadsheetId,
                $sheetName
            );

            $values = $response->getValues();
            Log::info('Read ' . (is_array($values) ? count($values) : 0) . ' rows from sheet ' . $sheetName);
            return $values ?: [];
        } catch (\Exception $e) {
            Log::error('Google Sheets API Error (readAllSheetData): ' . $e->getMessage());
            Log::error('Exception details: ' . json_encode($e->getMessage()));
            return [];
        }
    }

    /**
     * Update sheet data in Google Sheets
     *
     * @param string $range The range of cells to update
     * @param array $values The values to insert
     * @return bool
     */
    public function updateSheetData(string $range, array $values)
    {
        try {
            if (empty($this->spreadsheetId)) {
                Log::error('Spreadsheet ID not set before calling updateSheetData');
                throw new \Exception('Spreadsheet ID not set');
            }

            $body = new ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            Log::info('Updating sheet data for range: ' . $range);
            $this->sheetsService->spreadsheets_values->update(
                $this->spreadsheetId,
                $range,
                $body,
                $params
            );

            Log::info('Sheet data updated successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets API Error (updateSheetData): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Append data to Google Sheets
     *
     * @param string $range The range to append data to
     * @param array $values The values to append
     * @return bool
     */
    public function appendSheetData(string $range, array $values)
    {
        try {
            if (empty($this->spreadsheetId)) {
                Log::error('Spreadsheet ID not set before calling appendSheetData');
                throw new \Exception('Spreadsheet ID not set');
            }

            $body = new ValueRange([
                'values' => $values
            ]);

            $params = [
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS'
            ];

            Log::info('Appending sheet data for range: ' . $range);
            $this->sheetsService->spreadsheets_values->append(
                $this->spreadsheetId,
                $range,
                $body,
                $params
            );

            Log::info('Sheet data appended successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets API Error (appendSheetData): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update Google Sheet with data based on specific mapping
     *
     * @param string $spreadsheetId
     * @param string $sheetName
     * @param array $updateData
     * @return mixed
     */
    public function updateGoogleSheet($spreadsheetId, $sheetName, $updateData)
    {
        try {
            $this->setSpreadsheetId($spreadsheetId);

            // First, get the existing data from the sheet
            $range = "$sheetName!A:T";  // Adjust range as needed
            $sheetData = $this->getSheetData($range);

            if (empty($sheetData)) {
                throw new \Exception("No data found in the sheet");
            }

            // Find the column indexes
            $headerRow = $sheetData[0];
            $orderIdIndex = array_search('Order No', $headerRow);
            $statusIndex = array_search('Status', $headerRow);
            $podIndex = array_search('POD', $headerRow);

            if ($orderIdIndex === false || $statusIndex === false || $podIndex === false) {
                throw new \Exception("Required columns not found in the sheet");
            }

            $updates = [];

            foreach ($sheetData as $rowIndex => $row) {
                // Skip header row
                if ($rowIndex === 0) {
                    continue;
                }

                // Check if Order ID column exists
                if (!isset($row[$orderIdIndex])) {
                    continue;
                }

                $orderId = $row[$orderIdIndex];

                // Check if there is an update for this Order ID
                if (isset($updateData[$orderId])) {
                    $rowNumber = $rowIndex + 1; // 1-based index for Google Sheets

                    // Add update for Status
                    if (isset($updateData[$orderId]['status'])) {
                        $updates[] = [
                            'range' => "$sheetName!S$rowNumber",
                            'values' => [[$updateData[$orderId]['status']]]
                        ];
                    }

                    // Add update for POD
                    if (isset($updateData[$orderId]['pod'])) {
                        $updates[] = [
                            'range' => "$sheetName!R$rowNumber",
                            'values' => [[$updateData[$orderId]['pod']]]
                        ];
                    }

                    // Add update for Special Instructions if needed
                    if (isset($updateData[$orderId]['special_instruction'])) {
                        $updates[] = [
                            'range' => "$sheetName!U$rowNumber",
                            'values' => [[$updateData[$orderId]['special_instruction']]]
                        ];
                    }
                }
            }

            if (!empty($updates)) {
                foreach ($updates as $update) {
                    $this->updateSheetData($update['range'], $update['values']);
                }
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Error in updateGoogleSheet: " . $e->getMessage());
            throw $e;
        }
    }


    public function getSheetsService(): Sheets
    {
        return $this->sheetsService;
    }
}
