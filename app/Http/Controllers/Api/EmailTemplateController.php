<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmailTemplateRequest;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Http\Resources\EmailTemplateResource;
use Illuminate\Http\Response;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = EmailTemplate::paginate(15);
        return EmailTemplateResource::collection($templates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmailTemplateRequest $request)
    {
        $template = EmailTemplate::create($request->validated());
        return new EmailTemplateResource($template);
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return new EmailTemplateResource($emailTemplate);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate)
    {
        $emailTemplate->update($request->validated());
        return new EmailTemplateResource($emailTemplate);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $emailTemplate = EmailTemplate::findOrFail($id);
        $emailTemplate->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
