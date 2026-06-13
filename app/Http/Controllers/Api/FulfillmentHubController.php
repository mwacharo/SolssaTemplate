<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFulfillmentHubRequest;
use App\Http\Requests\UpdateFulfillmentHubRequest;
use App\Models\FulfillmentHub;
use App\Models\Vendor;
use App\Models\User;
use App\Models\Country;

class FulfillmentHubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hubs = FulfillmentHub::query()
            ->with(['country', 'agents', 'vendors'])
            ->latest()
            ->paginate(20);

        return response()->json($hubs);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $countries = Country::orderBy('name')->get();
        $vendors   = Vendor::orderBy('name')->get();
        $agents    = User::orderBy('name')->get();

        return view('fulfillment-hubs.create', compact('countries', 'vendors', 'agents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(StoreFulfillmentHubRequest $request)
    // {
    //     $hub = FulfillmentHub::create($request->safe()->only([
    //         'name',
    //         'country_id',
    //     ]));

    //     if ($request->filled('vendor_ids')) {
    //         Vendor::whereIn('id', $request->vendor_ids)
    //             ->update(['fulfillment_hub_id' => $hub->id]);
    //     }

    //     if ($request->filled('agent_ids')) {
    //         $hub->agents()->sync($request->agent_ids);
    //     }

    //     return redirect()
    //         ->route('fulfillment-hubs.show', $hub)
    //         ->with('success', 'Fulfillment hub created successfully.');
    // }


    // FulfillmentHubController.php
    public function store(StoreFulfillmentHubRequest $request)
    {
        $hub = FulfillmentHub::create($request->safe()->only(['name']));

        // ← sync() instead of update(); both use the pivot table
        if ($request->has('vendor_ids')) {
            $hub->vendors()->sync($request->vendor_ids ?? []);
        }

        if ($request->has('agent_ids')) {
            $hub->agents()->sync($request->agent_ids ?? []);
        }

        return response()->json([
            'message' => 'Fulfillment hub created successfully.',
            'hub' => $hub
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(FulfillmentHub $fulfillmentHub)
    {
        $fulfillmentHub->load([
            'country',
            'vendors',
            'agents',
            'lastCallAgent',
        ]);

        return view('fulfillment-hubs.show', compact('fulfillmentHub'));
    }

    /**
     * Show the form for editing the existing resource.
     */
    public function edit(FulfillmentHub $fulfillmentHub)
    {
        $fulfillmentHub->load(['vendors', 'agents']);

        $countries = Country::orderBy('name')->get();
        $vendors   = Vendor::orderBy('name')->get();
        $agents    = User::orderBy('name')->get();

        return view('fulfillment-hubs.edit', compact(
            'fulfillmentHub',
            'countries',
            'vendors',
            'agents',
        ));
    }

    public function update(
        UpdateFulfillmentHubRequest $request,
        FulfillmentHub $fulfillmentHub
    ) {
        $fulfillmentHub->update(
            $request->safe()->only([
                'name',
                'country_id',
                'last_call_agent_id',
            ])
        );

        if ($request->has('vendor_ids')) {
            $fulfillmentHub->vendors()->sync(
                $request->vendor_ids ?? []
            );
        }

        if ($request->has('agent_ids')) {
            $fulfillmentHub->agents()->sync(
                $request->agent_ids ?? []
            );
        }

        return response()->json([
            'success' => true,
            'data' => $fulfillmentHub->load([
                'country',
                'vendors',
                'agents',
                'lastCallAgent',
            ]),
        ]);
    }
    public function destroy(FulfillmentHub $fulfillmentHub)
    {
        $fulfillmentHub->agents()->detach();
        $fulfillmentHub->vendors()->detach();

        $fulfillmentHub->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fulfillment hub deleted.'
        ]);
    }
}
