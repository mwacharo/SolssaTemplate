<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Services\CallStatsService;


class CallCentreStatistics extends Controller
{
    //
// inject dependecies CallStatsService
   
   protected  $callStatsService;

    public function __construct(CallStatsService $CallStatsService)
    {
        $this->callStatsService = $CallStatsService;
    }
    

    public function AgentCallStats(Request $request, $id)
    {

        $user = User::find($id);

        // return response()->json([
        //     $user
        // ]);
        $dateRange = null;
        if ($request->has('date_range')) {
            $dateRange = $request->input('date_range');
            // Parse the date range input
            $dateRange = explode(',', $dateRange);
            if (count($dateRange) !== 2) {
                return response()->json(['error' => 'Invalid date range format.'], 400);
            }
            $dateRange[0] = Carbon::parse(trim($dateRange[0]))->startOfDay();
            $dateRange[1] = Carbon::parse(trim($dateRange[1]))->endOfDay();
        } else {
            // Default to today if no date range is provided
            // $dateRange = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
            $dateRange = [
                Carbon::now()->startOfYear(),  // Start of the current year
                Carbon::now()->endOfYear(),    // End of the current year
            ];
        }
        $stats = $this->callStatsService->getAgentStats($user, $dateRange);

        return response()->json($stats);
    }
}
