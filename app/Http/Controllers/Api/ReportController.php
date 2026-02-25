<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get filter options for reports
     */
    public function getOptions(): JsonResponse
    {
        try {
            $options = $this->reportService->getFilterOptions();

            return response()->json($options);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load report options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate report based on filters
     */
    public function generate(Request $request): JsonResponse
    {

        // add logs for debugging

        Log::info('Generating report with request data:', $request->all());
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:delivery,returns,dispatch,out_scan,undispatched,merchant,product,product_performance,zone',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',

            // Optional filters
            'merchant' => 'nullable|integer|exists:users,id',
            'product' => 'nullable|integer|exists:products,id',
            'category' => 'nullable|integer|exists:categories,id',
            'zone' => 'nullable|integer|exists:zones,id',
            'city' => 'nullable|integer|exists:cities,id',
            'rider' => 'nullable|integer|exists:users,id',
            'confirmationStatus' => 'nullable|string',
            'shippingStatus' => 'nullable|string',
            // 'orderDate' => 'nullable|array',
            'orderDate.start' => 'nullable|date',
            'orderDate.end' => 'nullable|date',
            'deliveryDate' => 'nullable|array',
            'deliveryDate.start' => 'nullable|date',
            'deliveryDate.end' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportType = $request->input('report_type');
            $filters = $request->except(['report_type', 'page', 'per_page', 'format']);
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);

            $result = $this->reportService->generateReport($reportType, $filters, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($result['total'] / $perPage)
            ]);
        } catch (\Exception $e) {
            \Log::error('Report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download report as Excel
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:delivery,returns,dispatch,out_scan,undispatched,merchant,product,product_performance,zone',
            'format' => 'required|string|in:xlsx,csv',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportType = $request->input('report_type');
            $filters = $request->except(['report_type', 'format']);
            $format = $request->input('format', 'xlsx');

            $file = $this->reportService->downloadReport($reportType, $filters, $format);

            return $file;
        } catch (\Exception $e) {
            \Log::error('Report download failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get report summary/statistics
     */
    public function getSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|string|in:delivery,returns,dispatch,out_scan,undispatched,merchant,product,product_performance,zone',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportType = $request->input('report_type');
            $filters = $request->except(['report_type']);

            $summary = $this->reportService->getReportSummary($reportType, $filters);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get report summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
