<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

use App\Services\GreenApiService;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{


    protected $greenApi;


    public function __construct(GreenApiService $greenApi)
    {
        $this->greenApi = $greenApi;
    }



    public function getState()
    {
        $state = $this->greenApi->getInstanceState();

        if (isset($state['error'])) {
            return response()->json(['status' => 'error', 'message' => $state['message']], 500);
        }

        return response()->json([
            'status' => 'success',
            'instance_state' => $state['stateInstance'],
        ]);
    }



  public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'user_id' => 'required|integer',
            'order_ids' => 'nullable|array',
            'contact_ids' => 'nullable|array',
        ]);

        $userId = $request->user_id;
        $messageText = $request->message;
        $queued = 0;

        // Send to orders' clients
        if ($request->filled('order_ids')) {
            $orders = Order::with('client')
                ->whereIn('id', $request->order_ids)
                ->get();

            foreach ($orders as $order) {
                $client = $order->client;

                $phone = $client?->phone_number ?? $client?->alt_phone_number;
                if (!$phone) continue;

                $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
                SendWhatsAppMessageJob::dispatch($chatId, $messageText, $userId);
                $queued++;
            }
        }

        // Send to user's contacts
        if ($request->filled('contact_ids')) {
            $user = User::with(['contacts' => function ($q) use ($request) {
                $q->whereIn('id', $request->contact_ids);
            }])->find($userId);

            foreach ($user?->contacts ?? [] as $contact) {
                $phone = $contact->phone ?? $contact->alt_phone;
                if (!$phone) continue;

                $chatId = preg_replace('/[^0-9]/', '', $phone) . '@c.us';
                SendWhatsAppMessageJob::dispatch($chatId, $messageText, $userId);
                $queued++;
            }
        }

        return response()->json([
            'status' => 'success',
            'queued_count' => $queued,
            'message' => "Queued $queued WhatsApp messages."
        ]);
    }

    public function getChat($phone)
    {
        // Check if the phone number already has '@c.us' suffix
        $waId = str_contains($phone, '@c.us') ? $phone : $phone . '@c.us';

        $messages = Message::where(function ($query) use ($waId) {
            $query->where('from', $waId)
                ->orWhere('to', $waId)
                ->orwhere('recipient_phone', $waId);
        })
            ->orderBy('timestamp', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch messages with pagination, only not deleted
        $messages = Message::whereNull('deleted_at')
            ->latest()
            ->paginate(20);

        // Return resource collection
        return MessageResource::collection($messages);
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
