<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerExpenseRequest;
use App\Http\Requests\UpdateSellerExpenseRequest;
use App\Models\SellerExpense;
use Illuminate\Http\Request;


use Symfony\Component\HttpFoundation\StreamedResponse;


class SellerExpenseController extends Controller
{
    /**
     * Allowed filter fields — whitelist prevents arbitrary column injection.
     * Time complexity: O(1) lookup via array_flip → isset()
     */
    private const FILTERABLE = [
        'vendor_id'       => 'exact',
        'expense_type'    => 'exact',
        'expense_type_id' => 'exact',
        'remittance_id'   => 'exact',
        'status'          => 'exact',
        'incurred_on'     => 'date',
        'created_at'      => 'date',
    ];

    /**
     * Display a listing of seller expenses with optional search/filter.
     *
     * DB-level filtering → O(log n) per indexed column (vs O(n) PHP-side scan).
     * Recommended indexes on: vendor_id, expense_type, expense_type_id,
     *   remittance_id, status, incurred_on, created_at
     *
     * Query string params:
     *   - per_page          int      rows per page (default 20, max 200)
     *   - search            string   full-text search on description
     *   - vendor_id         int
     *   - expense_type      string   'expense' | 'income'
     *   - expense_type_id   int
     *   - remittance_id     int
     *   - status            string   'not_applied' | 'applied' | 'approved' | 'rejected'
     *   - incurred_on       date     exact date  (YYYY-MM-DD)
     *   - incurred_on_from  date     range start (YYYY-MM-DD)
     *   - incurred_on_to    date     range end   (YYYY-MM-DD)
     *   - created_at        date     exact date
     *   - created_at_from   date     range start
     *   - created_at_to     date     range end
     *   - sort_by           string   column to sort (default 'created_at')
     *   - sort_dir          string   'asc' | 'desc' (default 'desc')
     */
    public function index(Request $request)
    {
        // ── Sanitise pagination ──────────────────────────────────────────────
        $perPage = min((int) $request->get('per_page', 20), 200);

        // ── Base query — eager-load only the relations we need ───────────────
        $query = SellerExpense::with(['vendor:id,username,email', 'expenseType:id,display_name']);

        // ── Full-text / LIKE search on description ───────────────────────────
        if ($search = $request->get('search')) {
            // Bind via parameterised query — no SQL injection risk
            $query->where('description', 'like', '%' . $search . '%');
        }

        // ── Exact-match & date filters ───────────────────────────────────────
        // Single O(k) loop over the whitelist (k = number of filter fields, constant)
        foreach (self::FILTERABLE as $field => $type) {
            if ($type === 'exact' && $request->filled($field)) {
                $query->where($field, $request->get($field));
                continue;
            }

            if ($type === 'date') {
                // Exact date takes priority over range
                if ($request->filled($field)) {
                    $query->whereDate($field, $request->get($field));
                } else {
                    if ($request->filled("{$field}_from")) {
                        $query->whereDate($field, '>=', $request->get("{$field}_from"));
                    }
                    if ($request->filled("{$field}_to")) {
                        $query->whereDate($field, '<=', $request->get("{$field}_to"));
                    }
                }
            }
        }

        // ── Sorting ──────────────────────────────────────────────────────────
        $sortableColumns = array_keys(self::FILTERABLE) + ['id', 'amount', 'updated_at'];
        $sortBy  = in_array($request->get('sort_by', 'created_at'), $sortableColumns, true)
            ? $request->get('sort_by', 'created_at')
            : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        // ── Execute — single paginated query ─────────────────────────────────
        $expenses = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success'  => true,
            'expenses' => $expenses,
        ]);
    }

    /**
     * Store a newly created seller expense.
     */
    public function store(StoreSellerExpenseRequest $request)
    {
        $expense = SellerExpense::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seller expense created successfully.',
            'data'    => $expense->load(['vendor:id,username,email', 'expenseType:id,display_name']),
        ], 201);
    }

    /**
     * Display the specified seller expense.
     */
    public function show(SellerExpense $sellerExpense)
    {
        return response()->json([
            'success' => true,
            'data'    => $sellerExpense->load(['vendor', 'expenseType']),
        ]);
    }

    /**
     * Update the specified seller expense.
     */
    public function update(UpdateSellerExpenseRequest $request, $id)
    {
        $sellerExpense = SellerExpense::findOrFail($id);
        $sellerExpense->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Seller expense updated successfully.',
            'data'    => $sellerExpense->fresh(['vendor:id,username,email', 'expenseType:id,display_name']),
        ]);
    }

    /**
     * Remove the specified seller expense.
     */
    public function destroy($id)
    {
        $sellerExpense = SellerExpense::findOrFail($id);
        $sellerExpense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seller expense deleted successfully.',
        ]);
    }



    public function export(Request $request): StreamedResponse
    {
        // ── Re-use the same filter logic as index() ──────────────────────────
        $query = SellerExpense::with(['vendor:id,username,full_name', 'expenseType:id,display_name'])
            ->select([
                'id',
                'vendor_id',
                'expense_type_id',
                'remittance_id',
                'description',
                'amount',
                'expense_type',
                'status',
                'incurred_on',
                'created_at',
            ]);

        if ($search = $request->get('search')) {
            $query->where('description', 'like', '%' . $search . '%');
        }

        foreach (self::FILTERABLE as $field => $type) {
            if ($type === 'exact' && $request->filled($field)) {
                $query->where($field, $request->get($field));
                continue;
            }
            if ($type === 'date') {
                if ($request->filled($field)) {
                    $query->whereDate($field, $request->get($field));
                } else {
                    if ($request->filled("{$field}_from")) {
                        $query->whereDate($field, '>=', $request->get("{$field}_from"));
                    }
                    if ($request->filled("{$field}_to")) {
                        $query->whereDate($field, '<=', $request->get("{$field}_to"));
                    }
                }
            }
        }

        $sortableColumns = array_merge(array_keys(self::FILTERABLE), ['id', 'amount', 'updated_at']);
        $sortBy  = in_array($request->get('sort_by', 'created_at'), $sortableColumns, true)
            ? $request->get('sort_by', 'created_at') : 'created_at';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // ── Stream CSV — O(1) memory via lazy cursor ─────────────────────────
        $filename = 'expenses_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'ID',
                'Vendor',
                'Category',
                'Remittance ID',
                'Description',
                'Amount (KES)',
                'Type',
                'Status',
                'Incurred On',
                'Created At',
            ]);

            // cursor() streams one row at a time — never loads full result set
            $query->cursor()->each(function ($expense) use ($handle) {
                fputcsv($handle, [
                    $expense->id,
                    $expense->vendor?->username ?? 'N/A',
                    $expense->expenseType?->display_name ?? 'N/A',
                    $expense->remittance_id ?? '',
                    $expense->description,
                    number_format($expense->amount, 2, '.', ''),
                    $expense->expense_type,
                    $expense->status,
                    $expense->incurred_on ?? '',
                    $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
