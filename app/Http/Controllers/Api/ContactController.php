<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = Contact::latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreContactRequest $request)
    {

        Log::info('Incoming contact store request', [
            'payload' => $request->all(),
            'validated' => method_exists($request, 'validated') ? $request->validated() : null,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);
        $contact = Contact::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $contact,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, string $id)
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
            ], 404);
        }

        Log::info('Incoming contact update request', [
            'id' => $id,
            'payload' => $request->all(),
            'validated' => $request->validated(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);

        $data = $request->validated();

        // Handle profile picture upload if present
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $path = $file->store('contacts', 'public');
            $data['profile_picture'] = $path;

            // Attempt to remove old picture if set
            if ($contact->profile_picture) {
                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($contact->profile_picture);
                } catch (\Exception $e) {
                    Log::warning('Failed to delete old profile picture', [
                        'contact_id' => $contact->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // Update the model
        $contact->update($data);

        return response()->json([
            'success' => true,
            'data' => $contact,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contact = Contact::find($id);

        if (! $contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
            ], 404);
        }

        Log::info('Incoming contact delete request', [
            'id' => $id,
            'ip' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);

        // Remove profile picture from storage if present
        if ($contact->profile_picture) {
            try {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($contact->profile_picture);
            } catch (\Exception $e) {
                Log::warning('Failed to delete profile picture during contact deletion', [
                    'contact_id' => $contact->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted',
        ]);
    }
}
