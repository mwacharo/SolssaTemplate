<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIvrOptionRequest;
use App\Http\Requests\UpdateIvrOptionRequest;
use App\Http\Resources\IvrOptionResource;
use App\Models\IvrOption;
use Illuminate\Http\Response;

class IvrOptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ivrOptions = IvrOption::paginate(15);
        return IvrOptionResource::collection($ivrOptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreIvrOptionRequest $request)
    {
        $ivrOption = IvrOption::create($request->validated());
        return (new IvrOptionResource($ivrOption))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(IvrOption $ivrOption)
    {
        return new IvrOptionResource($ivrOption);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIvrOptionRequest $request, IvrOption $ivrOption)
    {
        $ivrOption->update($request->validated());
        return new IvrOptionResource($ivrOption);
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(IvrOption $ivrOption)
    // {
    //     $ivrOption->delete();
    //     return response()->json(null, Response::HTTP_NO_CONTENT);
    public function destroy($id)
    {
        $ivrOption = IvrOption::findOrFail($id);
        $ivrOption->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
