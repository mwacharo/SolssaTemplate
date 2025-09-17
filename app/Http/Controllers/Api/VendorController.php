<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;


use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class VendorController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     $vendors = User::role('Vendor')
    //         // ->with('customers')
    //         ->whereNull('deleted_at')
    //         ->latest()
    //         ->paginate(20, ['*'], 'page', request()->get('page', 1));
    //     return VendorResource::collection($vendors);
    // }



    public function index()
    {
        $this->authorize('viewAny', User::class);

        $query = User::role('Vendor')
            ->whereNull('deleted_at')
            ->latest();

        if (auth()->user()->hasRole('Vendor')) {
            $query->where('id', auth()->id());
        }

        $vendors = $query->paginate(20, ['*'], 'page', request()->get('page', 1));

        return VendorResource::collection($vendors);
    }


    // /**
    //  * Show the form for creating a new resource.
    //  */
    // public function create()
    // {
    //     // Not needed for API
    //     return response()->json(['message' => 'Not implemented.'], 405);
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorRequest $request)
    {
        $vendor = Vendor::create($request->validated());
        return new VendorResource($vendor);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        return new VendorResource($vendor);
    }

    /**
     * Show the form for editing the specified resource.
     */
    // public function edit(Vendor $vendor)
    // {
    //     // Not needed for API
    //     return response()->json(['message' => 'Not implemented.'], 405);
    // }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $vendor->update($request->validated());
        return new VendorResource($vendor);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return response()->json(['message' => 'Vendor deleted successfully.']);
    }
}
