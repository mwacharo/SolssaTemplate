<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTemplateRequest;
use App\Http\Requests\UpdateTemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class TemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
           $templates = Template::latest()->paginate(20);
        return TemplateResource::collection($templates);
    }


    /**
     * Store a newly created resource in storage.
     */
    // public function store(StoreTemplateRequest $request)
    // {
    //     $template = Template::create($request->validated());
    //     return new TemplateResource($template);
    // }



    public function store(StoreTemplateRequest $request)
{
    $validated = $request->validated();

    // Dynamically determine the authenticated user's class and ID
    $validated['owner_type'] = get_class(Auth::user());
    $validated['owner_id'] = Auth::id();

    // Optional: If you're not accepting placeholders from user input, set them here
    $validated['placeholders'] = json_encode([
        'customer_name',
        'vendor_name',
        'offer_details',
        'offer_expiry_date',
        'product_name',
        'order_link',
        // etc.
    ]);

    $template = Template::create($validated);

    return new TemplateResource($template);
}

   

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTemplateRequest $request, string $id)
    {
        $template = Template::findOrFail($id);
        $template->update($request->validated());
        return new TemplateResource($template);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $template = Template::findOrFail($id);
        $template->delete();
        return response()->json(['message' => 'Template deleted successfully.'], 200);
    }
}
