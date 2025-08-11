<?php



namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;


use App\Http\Requests\StoreSmsRequest;
use App\Http\Requests\UpdateSmsRequest;
use App\Models\Sms;
use App\Services\AdvantaSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SmsController extends Controller
{
    protected $advantaSmsService;

    public function __construct(AdvantaSmsService $advantaSmsService)
    {
        $this->advantaSmsService = $advantaSmsService;
    }

        public function sendSms(Request $request)
        {

            $user_id =Auth::user()->id;
            // Log the incoming request data for debugging
            Log::debug('Received sendSms request', ['request_data' => $request->all()]);

            // Retrieve message from request, or use a default message
            $message = $request->input('message', 'Hello, this is a test message.');
            Log::debug('User ID', ['user_id' => $user_id]);
            Log::debug('Message to send', ['message' => $message]);

            // Call AdvantaSmsService to send a bulk message
            $template = $request->input('template');
            $contacts = $request->input('contacts', []);
            $data = [
                'message' => $message,
                'user_id' => $user_id,
                'contacts' => $contacts,
                // Optionally, for backward compatibility:
                'contact' => $contacts, // if single contact expected elsewhere
            ];
            $result = $this->advantaSmsService->sendBulkMessages($data, $template);
            Log::debug('Result from AdvantaSmsService::sendBulkMessages', ['result' => $result]);

            // Check for required user_id field in the request
            // if (!$request->has('user_id')) {
            //     Log::error('Validation error: user_id field is required.');
            //     return response()->json([
            //         'status' => 'error',
            //         'errors' => [
            //             'user_id' => ['The user id field is required.']
            //         ]
            //     ], 422);
            // }

            if ($result) {
                Log::info('SMS sent successfully');
                return response()->json(['message' => 'SMS sent successfully!'], 200);
            } else {
                Log::error('Failed to send SMS');
                return response()->json(['message' => 'Failed to send SMS.'], 500);
            }
        }



    public function index()
    {
        //
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
    public function store(StoreSmsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Sms $sms)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sms $sms)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSmsRequest $request, Sms $sms)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sms $sms)
    {
        //
    }
}
