<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CallHistory;
use App\Models\IvrOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;



class CallStatsService
{

    public function getAgentStats(User $user, ?array $dateRange = null): array
    {
        Log::info('Fetching agent stats', ['user_id' => $user->id, 'date_range' => $dateRange]);

        $isAdmin = $user->hasRole('CallCentreAdmin') || $user->hasRole('Admin');

        Log::info('User role check', ['user_id' => $user->id, 'is_admin' => $isAdmin]);



        $ivrOptions = IvrOption::all();

        if ($isAdmin) {
            $query = CallHistory::query()->whereNull('deleted_at');

            if ($dateRange) {
                $query->whereBetween('created_at', $dateRange);
            }

            // $incomingCalls = (clone $query)->whereNotNull('user_id')->count();
            $incomingCalls = (clone $query)->whereNull('clientDialledNumber')->count();


            // the outgoing call are made by users with client_name  as the callerNumber
            // $outgoingCalls = (clone $query)->where('callerNumber', $user->client_name)->count();
            // $outgoingCalls = (clone $query)->whereNotNull('callerNumber')->count();
            $outgoingCalls = $this->scopeSoftphoneOutgoing(clone $query)->count();
            $outgoingDuration = $this->scopeSoftphoneOutgoing(clone $query)->sum('durationInSeconds');

            $incomingDuration = (clone $query)->whereNotNull('user_id')->sum('durationInSeconds');
            // $outgoingDuration = (clone $query)->whereNotNull('callerNumber')->sum('durationInSeconds');

            $missedCalls = (clone $query)
                ->whereIn('lastBridgeHangupCause', ['NO_ANSWER', 'SERVICE_UNAVAILABLE'])
                ->count();

            $rejectedIncomingCalls = (clone $query)
                ->whereIn('lastBridgeHangupCause', ['CALL_REJECTED', 'NORMAL_CLEARING'])
                ->count();

            $userBusyOutgoingCalls = (clone $query)
                ->where('lastBridgeHangupCause', 'USER_BUSY')
                ->count();

            $rejectedOutgoingCalls = (clone $query)
                ->where('lastBridgeHangupCause', 'CALL_REJECTED')
                ->count();

            $ivrStats = CallHistory::whereNotNull('ivr_option_id')
                ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
                ->get();

            $ivrAnalysis = $this->analyzeOverallIvrStatistics($ivrOptions, $ivrStats, $dateRange);
            $airtimeStats = $this->getAirtimeStatistics($user, true, $dateRange);
            $peakHourStats = $this->getPeakHourStatistics($user, true, $dateRange);

            // Log::info('Returning stats for admin', ['user_id' => $user->id]);
        } else {
            // Agent-specific
            $incomingQuery = CallHistory::query()
                ->where('user_id', $user->id)
                ->whereNull('clientDialledNumber')
                ->whereNull('deleted_at');


            // Define and normalize phone safely (before closure use)
            $normalizedPhone = null;
            if (!empty($user->phone_number)) {
                // Keep digits only for consistent comparison
                $normalizedPhone = preg_replace('/\D+/', '', $user->phone_number);
            }

            // Logging for debugging outgoingQuery
            Log::info('Building outgoingQuery for agent', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'client_name' => $user->client_name,
            ]);

            // $outgoingQuery = CallHistory::query()
            //     // ->where('callerNumber', $user->phone_number)
            //     ->where('callerNumber', $user->client_name)
            //     ->whereNull('deleted_at');




            // Build outgoing query robustly:
            $outgoingQuery = CallHistory::query()->whereNull('deleted_at')
                ->where(function ($q) use ($user, $normalizedPhone) {
                    // Prefer direction column if present
                    if (Schema::hasColumn((new CallHistory())->getTable(), 'direction')) {
                        $q->where('direction', 'Outbound');
                    } else {
                        // Match by callerNumber = phone OR softphone client_name OR dotted softphone variants
                        if ($normalizedPhone) {
                            // remove non-digits from callerNumber in SQL and match
                            $q->whereRaw(
                                "REPLACE(REPLACE(REPLACE(callerNumber, '+', ''), ' ', ''), '-', '') LIKE ?",
                                ["%{$normalizedPhone}%"]
                            );
                        }

                        if (!empty($user->client_name)) {
                            // If a phone-match was already added, use OR for client_name matches
                            $q->orWhere('callerNumber', $user->client_name)
                                ->orWhere('callerNumber', 'like', "{$user->client_name}.%")
                                ->orWhere('callerNumber', 'like', "%.{$user->client_name}");
                            // Fallback: you could also do a generic like:
                            // ->orWhere('callerNumber', 'like', "%{$user->client_name}%")
                            // but that may be too broad in some datasets.
                        }
                    }
                });

            // Debug logging to inspect the eventually-built SQL / bindings & a sample
            Log::info('Built outgoingQuery for agent', [
                'user_id' => $user->id,
                'phone_number' => $user->phone_number,
                'client_name' => $user->client_name,
                'query_sql' => $outgoingQuery->toSql(),
                'bindings' => $outgoingQuery->getBindings(),
            ]);

            Log::info('OutgoingQuery built', [
                'query_sql' => $outgoingQuery->toSql(),
                'bindings' => $outgoingQuery->getBindings(),
            ]);

            if ($dateRange) {
                $incomingQuery->whereBetween('created_at', $dateRange);
                $outgoingQuery->whereBetween('created_at', $dateRange);
            }

            $incomingCalls = $incomingQuery->count();
            $outgoingCalls = $outgoingQuery->count();
            $incomingDuration = $incomingQuery->sum('durationInSeconds');
            $outgoingDuration = $outgoingQuery->sum('durationInSeconds');

            $missedCalls = (clone $incomingQuery)
                ->whereIn('lastBridgeHangupCause', ['NO_ANSWER', 'SERVICE_UNAVAILABLE'])
                ->count();

            $rejectedIncomingCalls = (clone $incomingQuery)
                ->whereIn('lastBridgeHangupCause', ['CALL_REJECTED', 'NORMAL_CLEARING'])
                ->count();

            $userBusyOutgoingCalls = (clone $outgoingQuery)
                ->where('lastBridgeHangupCause', 'USER_BUSY')
                ->count();

            $rejectedOutgoingCalls = (clone $outgoingQuery)
                ->where('lastBridgeHangupCause', 'CALL_REJECTED')
                ->count();

            $ivrStats = CallHistory::whereNotNull('ivr_option_id')
                ->where('user_id', $user->id)
                ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
                ->get();

            $ivrAnalysis = $this->analyzeIvrStatistics($ivrOptions, $ivrStats, $dateRange, $user->id);
            $airtimeStats = $this->getAirtimeStatistics($user, false, $dateRange);
            $peakHourStats = $this->getPeakHourStatistics($user, false, $dateRange);

            // Log::info('Returning stats for agent', ['user_id' => $user->id]);
        }

        return [
            'id' => $user->id,
            'phone_number' => $user->phone_number,
            'status' => $user->status,
            'sessionId' => $user->sessionId,
            'summary_call_completed' => $incomingCalls + $outgoingCalls,
            'summary_inbound_call_completed' => $incomingCalls,
            'summary_outbound_call_completed' => $outgoingCalls,
            'summary_call_duration' => $incomingDuration + $outgoingDuration,
            'summary_call_missed' => $missedCalls,
            'summary_rejected_incoming_calls' => $rejectedIncomingCalls,
            'summary_user_busy_outgoing_calls' => $userBusyOutgoingCalls,
            'summary_rejected_outgoing_calls' => $rejectedOutgoingCalls,
            'ivr_analysis' => $ivrAnalysis,
            'airtime_statistics' => $airtimeStats,
            'peak_hours' => $peakHourStats,
            'updated_at' => $user->updated_at,
        ];
    }



    private function scopeSoftphoneOutgoing($query)
    {
        $softphonePatterns = ['%.%', 'client_%', '%client_%', 'BoxleoKenya.%'];
        return $query->where(function ($q) use ($softphonePatterns) {
            foreach ($softphonePatterns as $pattern) {
                $q->orWhere('callerNumber', 'like', $pattern);
            }
        });
    }


    // scope for incoming calls




    protected function getAirtimeStatistics(User $user, bool $isAdmin, ?array $dateRange = null): array
    {
        $incomingQuery = CallHistory::query()->whereNull('deleted_at');
        $outgoingQuery = CallHistory::query()->whereNull('deleted_at');

        if (!$isAdmin) {
            $incomingQuery->where('user_id', $user->id);
            $outgoingQuery->where('callerNumber', $user->phone_number);
        }

        if ($dateRange) {
            $incomingQuery->whereBetween('created_at', $dateRange);
            $outgoingQuery->whereBetween('created_at', $dateRange);

            $incomingAmount = $incomingQuery->sum('amount');
            $outgoingAmount = $outgoingQuery->sum('amount');

            $incomingDuration = $incomingQuery->sum('durationInSeconds');
            $outgoingDuration = $outgoingQuery->sum('durationInSeconds');

            return [
                // Airtime (Amount)
                'total_airtime_spent' => round($incomingAmount + $outgoingAmount, 2),
                'incoming_airtime_spent' => round($incomingAmount, 2),
                'outgoing_airtime_spent' => round($outgoingAmount, 2),

                // Duration
                'total_airtime_seconds' => $incomingDuration + $outgoingDuration,
                'incoming_airtime_seconds' => $incomingDuration,
                'outgoing_airtime_seconds' => $outgoingDuration,
                'total_airtime_minutes' => round(($incomingDuration + $outgoingDuration) / 60, 2),
            ];
        }

        // Group by day when no date range provided
        $incoming = $incomingQuery->selectRaw('DATE(created_at) as date, SUM(amount) as total_amount, SUM(durationInSeconds) as total_duration')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $outgoing = $outgoingQuery->selectRaw('DATE(created_at) as date, SUM(amount) as total_amount, SUM(durationInSeconds) as total_duration')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $dailyStats = [];

        foreach ($incoming as $in) {
            $dailyStats[$in->date]['incoming_amount'] = $in->total_amount;
            $dailyStats[$in->date]['incoming_duration'] = $in->total_duration;
        }

        foreach ($outgoing as $out) {
            $dailyStats[$out->date]['outgoing_amount'] = $out->total_amount;
            $dailyStats[$out->date]['outgoing_duration'] = $out->total_duration;
        }

        $results = [];
        foreach ($dailyStats as $date => $values) {
            $inAmount = $values['incoming_amount'] ?? 0;
            $outAmount = $values['outgoing_amount'] ?? 0;
            $inDuration = $values['incoming_duration'] ?? 0;
            $outDuration = $values['outgoing_duration'] ?? 0;

            $results[$date] = [
                // Airtime (Amount)
                'incoming_airtime_spent' => round($inAmount, 2),
                'outgoing_airtime_spent' => round($outAmount, 2),
                'total_airtime_spent' => round($inAmount + $outAmount, 2),

                // Duration
                'incoming_airtime_seconds' => $inDuration,
                'outgoing_airtime_seconds' => $outDuration,
                'total_airtime_seconds' => $inDuration + $outDuration,
                'total_airtime_minutes' => round(($inDuration + $outDuration) / 60, 2),
            ];
        }

        return $results;
    }



    protected function getPeakHourStatistics(User $user, bool $isAdmin, ?array $dateRange = null): array
    {
        $incomingQuery = CallHistory::query()
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupByRaw('HOUR(created_at)')
            ->orderBy('hour');

        $outgoingQuery = CallHistory::query()
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->whereNull('deleted_at')
            ->groupByRaw('HOUR(created_at)')
            ->orderBy('hour');

        if (!$isAdmin) {
            $incomingQuery->where('user_id', $user->id);
            $outgoingQuery->where('callerNumber', $user->phone_number);
        }

        if ($dateRange) {
            $incomingQuery->whereBetween('created_at', $dateRange);
            $outgoingQuery->whereBetween('created_at', $dateRange);
        }

        $incoming = $incomingQuery->get();
        $outgoing = $outgoingQuery->get();

        // Combine into a neat structure
        $peakStats = [];

        foreach ($incoming as $row) {
            $hour = str_pad($row->hour, 2, '0', STR_PAD_LEFT) . ':00';
            $peakStats[$hour]['incoming'] = $row->total;
        }

        foreach ($outgoing as $row) {
            $hour = str_pad($row->hour, 2, '0', STR_PAD_LEFT) . ':00';
            $peakStats[$hour]['outgoing'] = $row->total;
        }

        // Add total + identify peak
        $busiestHour = null;
        $maxCalls = 0;

        foreach ($peakStats as $hour => &$stats) {
            $stats['incoming'] = $stats['incoming'] ?? 0;
            $stats['outgoing'] = $stats['outgoing'] ?? 0;
            $stats['total'] = $stats['incoming'] + $stats['outgoing'];

            if ($stats['total'] > $maxCalls) {
                $maxCalls = $stats['total'];
                $busiestHour = $hour;
            }
        }

        return [
            'peak_hour_data' => $peakStats,
            'busiest_hour' => $busiestHour,
            'call_count' => $maxCalls,
        ];
    }


    public function analyzeIvrStatistics(Collection $ivrOptions, Collection $ivrStats, ?array $dateRange = null, int $userId = null): Collection
    {
        if ($userId !== null) {
            $ivrStats = $ivrStats->where('user_id', $userId);
            // Log::info('After filtering by user_id', ['user_id' => $userId, 'ivrStats' => $ivrStats->pluck('ivr_option_id')]);
        }

        if ($dateRange !== null) {
            $ivrStats = $ivrStats->filter(function ($stat) use ($dateRange) {
                $createdAt = Carbon::parse($stat->created_at);
                return $createdAt->between($dateRange[0], $dateRange[1]);
            });
            // Log::info('After filtering by date range', ['dateRange' => $dateRange, 'ivrStats' => $ivrStats->pluck('ivr_option_id')]);
        }

        $totalSelections = $ivrStats->count();
        // Log::info('Total selections after all filters', ['total' => $totalSelections]);

        return $ivrOptions->map(function ($ivrOption) use ($ivrStats, $totalSelections) {
            $matchedStats = $ivrStats->where('ivr_option_id', $ivrOption->id);

            // Log::info('Stats for IVR Option', [
            //     'option_id' => $ivrOption->id,
            //     'description' => $ivrOption->description,
            //     'matched_count' => $matchedStats->count()
            // ]);

            $totalSelected = $matchedStats->count();
            $totalDuration = $matchedStats->sum('durationInSeconds') ?? 0;

            return [
                'id' => $ivrOption->id,
                'option_number' => $ivrOption->option_number,
                'description' => $ivrOption->description,
                'total_selected' => $totalSelected,
                'total_duration' => $totalDuration,
                'average_duration' => $totalSelected ? round($totalDuration / $totalSelected, 2) : 0,
                'selection_percentage' => $totalSelections ? round(($totalSelected / $totalSelections) * 100, 2) : 0,
            ];
        });
    }


    //  Top Calling Countries
    public function topCallerCountries(array $filters = []): Collection
    {
        $query = CallHistory::query()
            ->whereNull('deleted_at')
            ->whereNotNull('callerCountryCode');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        return $query->selectRaw('callerCountryCode, COUNT(*) as total')
            ->groupBy('callerCountryCode')
            ->orderByDesc('total')
            ->get();
    }
    // . Frequent Callers


    public function frequentCallers(array $filters = [], int $limit = 10): Collection
    {
        $query = CallHistory::query()
            ->whereNull('deleted_at');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        return $query->selectRaw('callerNumber, COUNT(*) as call_count')
            ->groupBy('callerNumber')
            ->orderByDesc('call_count')
            ->limit($limit)
            ->get();
    }
    // Agent Comparison Report


    public function agentPerformanceComparison(array $filters = []): Collection
    {
        $query = CallHistory::query()
            ->whereNull('deleted_at')
            ->whereNotNull('user_id');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        return $query->with('agent')
            ->get()
            ->groupBy('user_id')
            ->map(function ($calls) {
                return [
                    'agent' => optional($calls->first()->agent)->name ?? 'Unknown',
                    'total_calls' => $calls->count(),
                    'average_duration' => round($calls->avg('durationInSeconds'), 2),
                    'answered_calls' => $calls->where('status', 'Answered')->count(),
                    'missed_calls' => $calls->where('status', 'Missed')->count(),
                ];
            })->values();
    }


    public function getOverallCallStats(array $filters = []): array
    {
        $startDate = isset($filters['start_date'])
            ? Carbon::parse($filters['start_date'])->startOfDay()
            : Carbon::today()->startOfDay();

        $endDate = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'])->endOfDay()
            : Carbon::today()->endOfDay();

        $baseQuery = CallHistory::whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at');

        // Call summaries
        $totalCalls = (clone $baseQuery)->count();
        $inboundCalls = (clone $baseQuery)->whereNotNull('user_id')->count();
        $outboundCalls = (clone $baseQuery)->whereNull('user_id')->count();

        $missedCalls = (clone $baseQuery)->whereIn('lastBridgeHangupCause', ['NO_ANSWER', 'SERVICE_UNAVAILABLE'])->count();
        $rejectedIncomingCalls = (clone $baseQuery)
            ->whereNotNull('user_id')
            ->whereIn('lastBridgeHangupCause', ['CALL_REJECTED', 'NORMAL_CLEARING'])
            ->count();

        $userBusyOutgoingCalls = (clone $baseQuery)
            ->whereNull('user_id')
            ->where('lastBridgeHangupCause', 'USER_BUSY')
            ->count();

        $rejectedOutgoingCalls = (clone $baseQuery)
            ->whereNull('user_id')
            ->where('lastBridgeHangupCause', 'CALL_REJECTED')
            ->count();

        $totalDuration = (clone $baseQuery)->sum('durationInSeconds') ?? 0;

        // IVR stats delegation
        $ivrStats = CallHistory::whereNotNull('ivr_option_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->get();

        $ivrOptions = IVROption::all();

        $ivrBreakdown = $this->analyzeOverallIvrStatistics($ivrOptions, $ivrStats, [$startDate, $endDate]);

        return [
            'summary_from' => $startDate->toDateTimeString(),
            'summary_to' => $endDate->toDateTimeString(),
            'summary_call_completed' => $totalCalls,
            'summary_inbound_call_completed' => $inboundCalls,
            'summary_outbound_call_completed' => $outboundCalls,
            'summary_call_duration' => $totalDuration,
            'summary_call_missed' => $missedCalls,
            'summary_rejected_incoming_calls' => $rejectedIncomingCalls,
            'summary_user_busy_outgoing_calls' => $userBusyOutgoingCalls,
            'summary_rejected_outgoing_calls' => $rejectedOutgoingCalls,
            'ivr_breakdown' => $ivrBreakdown,
        ];
    }



    public function analyzeOverallIvrStatistics(Collection $ivrOptions, Collection $ivrStats, ?array $dateRange = null): Collection
    {
        if ($dateRange !== null) {
            $ivrStats = $ivrStats->filter(function ($stat) use ($dateRange) {
                $createdAt = Carbon::parse($stat->created_at);
                return $createdAt->between($dateRange[0], $dateRange[1]);
            });

            // Log::info('Filtered IVR stats for overall report', [
            //     'dateRange' => $dateRange,
            //     'filtered_count' => $ivrStats->count(),
            // ]);
        }

        $totalSelections = $ivrStats->count();

        return $ivrOptions->map(function ($ivrOption) use ($ivrStats, $totalSelections) {
            $matchedStats = $ivrStats->where('ivr_option_id', $ivrOption->id);

            $totalSelected = $matchedStats->count();
            $totalDuration = $matchedStats->sum('durationInSeconds') ?? 0;

            return [
                'id' => $ivrOption->id,
                'option_number' => $ivrOption->option_number,
                'description' => $ivrOption->description,
                'total_selected' => $totalSelected,
                'total_duration' => $totalDuration,
                'average_duration' => $totalSelected ? round($totalDuration / $totalSelected, 2) : 0,
                'selection_percentage' => $totalSelections ? round(($totalSelected / $totalSelections) * 100, 2) : 0,
            ];
        });
    }




    // IVR Trends Over Time
    public function ivrTrendsByDate(array $filters = []): Collection
    {
        $query = CallHistory::query()
            ->whereNotNull('agentId')
            ->whereNull('deleted_at');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        return $query->selectRaw('DATE(created_at) as date, agentId, COUNT(*) as total')
            ->groupBy('date', 'agentId')
            ->get()
            ->groupBy('agentId');
    }

    // Call Drop-off / Short Duration Analysis


    public function analyzeCallDropOffs(array $filters = []): array
    {
        $query = CallHistory::query()->whereNull('deleted_at');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        $shortCalls = (clone $query)->where('durationInSeconds', '<', 10)->count();
        $missedCalls = (clone $query)->whereIn('lastBridgeHangupCause', ['NO_ANSWER', 'SERVICE_UNAVAILABLE'])->count();
        $totalCalls = (clone $query)->count();

        return [
            'total_calls' => $totalCalls,
            'short_calls' => $shortCalls,
            'missed_calls' => $missedCalls,
            'drop_off_rate' => $totalCalls > 0 ? round(($shortCalls + $missedCalls) / $totalCalls * 100, 2) . '%' : '0%'
        ];
    }

    // Peak Call Times (Hour of Day + Day of Week)


    public function peakCallTimes(array $filters = []): array
    {
        $query = CallHistory::query()->whereNull('deleted_at');

        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate']]);
        }

        $hourly = (clone $query)->selectRaw('HOUR(created_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour');

        $daily = (clone $query)->selectRaw('DAYNAME(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderByRaw("FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->pluck('total', 'day');

        return [
            'hourly_distribution' => $hourly,
            'daily_distribution' => $daily,
        ];
    }
}
