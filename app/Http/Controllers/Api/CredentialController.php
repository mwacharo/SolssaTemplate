<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChannelCredential;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class CredentialController extends Controller
{


    /**
     * Helper function to get the owner model
     */
    private function getOwnerModel(string $type, int $id)
    {
        switch ($type) {
            case 'App\\Models\\Vendor':
                return Vendor::find($id);

                // case 'App\\Models\\Team':
                //     return Team::find($id);

            case 'App\\Models\\User':
                return User::find($id);

            default:
                return null;
        }
    }




    public function getCredentialableTypes()
    {
        try {
            $types = [
                "App\\Models\\Vendor",
                "App\\Models\\Team",
                "App\\Models\\User"
            ];

            return response()->json([
                'success' => true,
                'types' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve credentialable types',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getOwnersByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Type parameter is required',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;

        try {
            $owners = [];

            switch ($type) {
                case 'App\\Models\\Vendor':
                    $owners = Vendor::select('id', 'name')->get();
                    break;

                // case 'App\\Models\\Team':
                //     $owners = Team::select('id', 'name')->get();
                //     break;

                case 'App\\Models\\User':
                    $owners = User::select('id', 'name')->get();
                    break;

                default:
                    return response()->json([
                        'message' => 'Invalid credentialable type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'owners' => $owners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve owners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchCredentials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'credentialable_type' => 'required|string',
            'credentialable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $credentials = ChannelCredential::where('credentialable_type', $request->credentialable_type)
                ->where('credentialable_id', $request->credentialable_id)
                ->get();

            return response()->json([
                'success' => true,
                'credentials' => $credentials
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving credentials: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve credentials',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {


        $credentials = ChannelCredential::all();


        return response()->json([
            'success' => true,
            'credentials' => $credentials
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        Log::info('Store Credential Request Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'credentialable_type' => 'required|string',
            'credentialable_id' => 'required|integer',
            'channel' => 'required|string',
            'provider' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentialableType = $request->credentialable_type;
        $credentialableId = $request->credentialable_id;

        // Check if owner exists
        $owner = $this->getOwnerModel($credentialableType, $credentialableId);
        if (!$owner) {
            return response()->json([
                'message' => 'Owner not found'
            ], 404);
        }

        try {
            $credential = ChannelCredential::updateOrCreate(
                [
                    'credentialable_type' => $credentialableType,
                    'credentialable_id' => $credentialableId,
                    'channel' => $request->channel
                ],
                [
                    'provider' => $request->provider,
                    'from_name' => $request->from_name,
                    'from_address' => $request->from_address,
                    'smtp_host' => $request->smtp_host,
                    'smtp_port' => $request->smtp_port,
                    'encryption' => $request->encryption,
                    'api_key' => $request->api_key,
                    'api_secret' => $request->api_secret,
                    'access_token' => $request->access_token,
                    'access_token_secret' => $request->access_token_secret,
                    'auth_token' => $request->auth_token,
                    'client_id' => $request->client_id,
                    'client_secret' => $request->client_secret,
                    'user_name' => $request->user_name,
                    'password' => $request->password,
                    'account_sid' => $request->account_sid,
                    'account_id' => $request->account_id,
                    'app_id' => $request->app_id,
                    'app_secret' => $request->app_secret,
                    'page_access_token' => $request->page_access_token,
                    'page_id' => $request->page_id,
                    'phone_number' => $request->phone_number,
                    'instance_id' => $request->instance_id,
                    'api_token' => $request->api_token,
                    'api_url' => $request->api_url,
                    'email_address' => $request->email_address,
                    'webhook' => $request->webhook,
                    'status' => $request->status,
                    'value' => $request->value,
                    'description' => $request->description,
                    'meta' => $request->meta,
                    'mail_mailer' => $request->mail_mailer
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Credential saved successfully',
                'credential' => $credential
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving credential: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to save credential',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $credential = ChannelCredential::findOrFail($id);
            return response()->json([
                'success' => true,
                'credential' => $credential
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Credential not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {


        // log the entire request data for debugging

        Log::info('Update Credential Request Data:', $request->all());


        $validator = Validator::make($request->all(), [
            'provider' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $credential = ChannelCredential::findOrFail($id);

            // Update credential fields
            $credential->provider = $request->provider ?? $credential->provider;
            $credential->from_name = $request->from_name ?? $credential->from_name;
            $credential->from_address = $request->from_address ?? $credential->from_address;
            $credential->smtp_host = $request->smtp_host ?? $credential->smtp_host;
            $credential->smtp_port = $request->smtp_port ?? $credential->smtp_port;
            $credential->encryption = $request->encryption ?? $credential->encryption;
            $credential->api_key = $request->api_key ?? $credential->api_key;
            $credential->api_secret = $request->api_secret ?? $credential->api_secret;
            $credential->access_token = $request->access_token ?? $credential->access_token;
            $credential->access_token_secret = $request->access_token_secret ?? $credential->access_token_secret;
            $credential->auth_token = $request->auth_token ?? $credential->auth_token;
            $credential->client_id = $request->client_id ?? $credential->client_id;
            $credential->client_secret = $request->client_secret ?? $credential->client_secret;
            $credential->user_name = $request->user_name ?? $credential->user_name;
            $credential->password = $request->password ?? $credential->password;
            $credential->account_sid = $request->account_sid ?? $credential->account_sid;
            $credential->account_id = $request->account_id ?? $credential->account_id;
            $credential->app_id = $request->app_id ?? $credential->app_id;
            $credential->app_secret = $request->app_secret ?? $credential->app_secret;
            $credential->page_access_token = $request->page_access_token ?? $credential->page_access_token;
            $credential->page_id = $request->page_id ?? $credential->page_id;
            $credential->phone_number = $request->phone_number ?? $credential->phone_number;
            $credential->instance_id = $request->instance_id ?? $credential->instance_id;
            $credential->api_token = $request->api_token ?? $credential->api_token;
            $credential->api_url = $request->api_url ?? $credential->api_url;
            $credential->email_address = $request->email_address ?? $credential->email_address;
            $credential->webhook = $request->webhook ?? $credential->webhook;
            $credential->status = $request->status;
            $credential->value = $request->value ?? $credential->value;
            $credential->description = $request->description ?? $credential->description;
            $credential->meta = $request->meta ?? $credential->meta;
            $credential->mail_mailer = $request->mail_mailer ?? $credential->mail_mailer;

            $credential->save();

            return response()->json([
                'success' => true,
                'message' => 'Credential updated successfully',
                'credential' => $credential
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update credential',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
