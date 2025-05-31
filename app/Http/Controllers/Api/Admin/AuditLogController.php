<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Spatie\Activitylog\Models\Activity;


class AuditLogController extends Controller
{
    //

      public function index(Request $request)
    {
        $query = Activity::with('causer');

        // Optional filtering by action/event
        if ($request->has('action')) {
            $query->where('event', $request->input('action'));
        }

        // Optional date range filter
        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        $logs = $query->latest()->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => ucfirst($log->event) . ' ' . class_basename($log->subject_type),
                'description' => $log->description ?? '-',
                'timestamp' => $log->created_at,
                'details' => $log->properties,
                'user' => [
                    'name' => optional($log->causer)->name ?? 'System',
                    'avatar' => 'https://randomuser.me/api/portraits/men/' . rand(1, 10) . '.jpg',
                ]
            ];
        });

        return response()->json($logs);
    }
}
