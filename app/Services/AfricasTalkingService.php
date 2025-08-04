<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use App\Events\CallStatusUpdated;
use App\Models\Call;
use App\Models\CallHistory;
use App\Models\CallQueue;
use App\Models\IvrOption;
use App\Models\Officer;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Contact;
use App\Models\CallCenterSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use PHPUnit\Framework\Attributes\Ticket as AttributesTicket;

class AfricasTalkingService
{
    protected $africastalking;
    protected $config;
    protected $callStatsService;

    public function __construct(CallStatsService $callStatsService)
    {
        $this->callStatsService = $callStatsService;
        $this->config = $this->getCallConfig();
        $this->initializeAfricasTalking();
    }

    /**
     * Get configuration for voice calls
     */
    private function getCallConfig(): array
    {
       
        $settings = CallCenterSetting::first();

        return [
            'africastalking' => [
            'country_id' => $settings['country_id'] ?? null,
            'username' => $settings['username'] ?? null,
            'api_key' => $settings['api_key'] ?? null,
            'phone' => $settings['phone'] ?? null,
            'sandbox' => isset($settings['sandbox']) ? (bool)$settings['sandbox'] : false,
            ],
            'voice' => [
            'default_voice' => $settings['default_voice'] ?? 'woman',
            'timeout' => isset($settings['timeout']) ? (int)$settings['timeout'] : 3,
            'recording_enabled' => isset($settings['recording_enabled']) ? (bool)$settings['recording_enabled'] : true,
            ],
            'urls' => [
            'callback_url' => $settings['callback_url'] ?? null,
            'event_callback_url' => $settings['event_callback_url'] ?? null,
            'ringback_tone' => $settings['ringback_tone'] ?? null,
            'voicemail_callback' => $settings['voicemail_callback'] ?? null,
            ],
            'messages' => [
            'welcome' => $settings['welcome_message'] ?? 'Welcome to our service.',
            'no_input' => $settings['no_input_message'] ?? 'We did not receive any input. Goodbye.',
            'invalid_option' => $settings['invalid_option_message'] ?? 'Invalid option selected. Please try again.',
            'connecting_agent' => $settings['connecting_agent_message'] ?? 'Connecting you to an agent.',
            'agents_busy' => $settings['agents_busy_message'] ?? 'All agents are currently busy. Please leave a message.',
            'voicemail_prompt' => $settings['voicemail_prompt'] ?? 'Please leave a message after the tone.',
            ],
            'fallback_number' => $settings['fallback_number'] ?? null,
            'default_forward_number' => $settings['default_forward_number'] ?? null,
            'debug_mode' => isset($settings['debug_mode']) ? (bool)$settings['debug_mode'] : false,
            'log_level' => $settings['log_level'] ?? 'info',
        ];
    }

    /**
     * Initialize Africa's Talking SDK
     */
    private function initializeAfricasTalking(): void
    {
        $username = $this->config['africastalking']['username'];
        $apiKey = $this->config['africastalking']['api_key'];

        if (!$username || !$apiKey) {
            throw new Exception('Africa\'s Talking credentials are missing');
        }

        $this->africastalking = new AfricasTalking($username, $apiKey);
    }

    /**
     * Make an outgoing call
     */
    public function makeCall(string $phoneNumber, array $options = []): array
    {
        Log::info('Initiating call', ['phone' => $phoneNumber, 'options' => $options]);

        $callParams = array_merge([
            'from' => $this->config['africastalking']['phone'],
            'to' => $phoneNumber,
        ], $options);

        try {
            $response = $this->africastalking->voice()->call($callParams);
            Log::info('Call initiated successfully', ['response' => $response]);

            return [
                'success' => true,
                'message' => 'Call initiated successfully',
                'data' => $response,
            ];
        } catch (Exception $e) {
            Log::error('Failed to initiate call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate call',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle voice callback with configurable routing
     */
    public function handleVoiceCallback(Request $request): string
    {
        Log::info('Received voice callback', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        $sessionId = $request->input('sessionId');
        if (!$sessionId) {
            Log::warning('Missing sessionId in voice callback request');
            throw new Exception('Session ID is required');
        }

        $isActive = $request->boolean('isActive', false);
        $callerNumber = $request->input('callerNumber');
        $destinationNumber = $request->input('destinationNumber', '');
        $clientDialedNumber = $request->input('clientDialedNumber', '');
        $callSessionState = $request->input('callSessionState', '');

        $isOutgoing = $this->isOutgoingCall($callerNumber);

        if ($isOutgoing) {
            return $this->handleOutgoingCall($request, $sessionId, $callSessionState, $callerNumber, $clientDialedNumber);
        } else {
            return $this->handleIncomingCall($request, $sessionId, $callerNumber);
        }
    }

    /**
     * Check if call is outgoing based on configurable patterns
     */
    private function isOutgoingCall(string $callerNumber): bool
    {
        $outgoingPatterns = config('voice.outgoing_patterns', ['BoxleoKenya']);
        
        foreach ($outgoingPatterns as $pattern) {
            if (str_contains($callerNumber, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle outgoing call states
     */
    private function handleOutgoingCall(Request $request, string $sessionId, string $callSessionState, string $callerNumber, string $clientDialedNumber): string
    {
        Log::info("Handling outgoing call state: {$callSessionState}", [
            'sessionId' => $sessionId,
            'callerNumber' => $callerNumber,
            'clientDialedNumber' => $clientDialedNumber
        ]);

        switch ($callSessionState) {
            case 'Ringing':
                $this->updateAgentStatus($callerNumber, $sessionId, 'busy');
                return $this->generateDialResponse($clientDialedNumber);

            case 'CallInitiated':
                $this->updateCallHistory($sessionId, ['status' => 'initiated']);
                break;

            case 'CallConnected':
                $this->updateCallHistory($sessionId, ['status' => 'connected']);
                break;

            case 'CallTerminated':
                if ($request->boolean('isActive')) {
                    $this->updateCallHistory($sessionId, [
                        'callerNumber' => $callerNumber,
                        'destinationNumber' => $clientDialedNumber,
                        'direction' => 'outgoing',
                        'isActive' => 1,
                    ]);
                }
                break;

            case 'Completed':
                $this->finalizeCall($request, $sessionId);
                $this->resetAgentStatus($sessionId);
                break;

            default:
                Log::warning("Unhandled outgoing call state: {$callSessionState}");
                break;
        }

        return '';
    }

    /**
     * Handle incoming call routing
     */
    private function handleIncomingCall(Request $request, string $sessionId, string $callerNumber): string
    {
        // Handle DTMF input first
        if ($request->has('dtmfDigits')) {
            Log::info('Processing DTMF input', [
                'dtmfDigits' => $request->input('dtmfDigits'),
                'callerNumber' => $callerNumber
            ]);
            return $this->handleDtmfSelection(
                $request->input('dtmfDigits'),
                $callerNumber,
                $sessionId
            );
        }

        // Try to assign available agent based on priority routing
        $assignedAgentNumber = $this->getAvailableAgent($callerNumber, $sessionId);
        
        if ($assignedAgentNumber) {
            Log::info("Routing call to assigned agent", ['agentNumber' => $assignedAgentNumber]);
            return $this->createDialResponse($assignedAgentNumber);
        }

        // No agent available, show IVR menu
        Log::info("No available agent found, showing IVR menu");
        return $this->generateDynamicMenu();
    }

    /**
     * Get available agent with priority routing
     */
    public function getAvailableAgent(string $callerNumber, string $sessionId): ?string
    {
        Log::info('Checking for available agents with priority routing', [
            'callerNumber' => $callerNumber
        ]);

        // Priority 1: Check if caller has an open ticket
        $agent = $this->getAgentFromTicket($callerNumber);
        if ($agent) {
            return $this->assignAgent($agent, $sessionId);
        }

        // Priority 2: Check if caller is in agent contacts
        $agent = $this->getAgentFromContacts($callerNumber);
        if ($agent) {
            return $this->assignAgent($agent, $sessionId);
        }

        // Priority 3: Check for recent orders
        $agent = $this->getAgentFromOrders($callerNumber);
        if ($agent) {
            return $this->assignAgent($agent, $sessionId);
        }

        // Priority 4: Round-robin assignment to available agents
        $agent = $this->getNextAvailableAgent();
        if ($agent) {
            return $this->assignAgent($agent, $sessionId);
        }

        return null;
    }

    /**
     * Get agent from open tickets
     */
    private function getAgentFromTicket(string $callerNumber): ?User
    {
        if (!class_exists(Ticket::class)) {
            return null;
        }

        $ticket = Ticket::where('phone_number', $callerNumber)
            ->where('status', 'open')
            ->first();

        if (!$ticket) {
            return null;
        }

        return User::where('id', $ticket->assigned_to)
            ->where('status', 'available')
            ->first();
    }

    /**
     * Get agent from contacts
     */
    private function getAgentFromContacts(string $callerNumber): ?User
    {
        if (!class_exists(Contact::class)) {
            return null;
        }

        $contact = Contact::where('phone_number', $callerNumber)->first();
        
        if (!$contact) {
            return null;
        }

        return User::where('id', $contact->user_id)
            ->where('status', 'available')
            ->first();
    }

    /**
     * Get agent from recent orders
     */
    private function getAgentFromOrders(string $callerNumber): ?User
    {
        if (!class_exists(Order::class)) {
            return null;
        }

        $order = Order::where('customer_phone', $callerNumber)
            ->latest()
            ->first();

        if (!$order || !$order->agent_id) {
            return null;
        }

        return User::where('id', $order->agent_id)
            ->where('status', 'available')
            ->first();
    }

    /**
     * Get next available agent using round-robin or other logic
     */
    private function getNextAvailableAgent(): ?User
    {
        $availableAgents = User::where('status', 'ready')->get();

        // Filter agents with permission to receive calls
        $availableAgents = $availableAgents->filter(function ($user) {
            return method_exists($user, 'hasPermissionTo') ? 
                $user->hasPermissionTo('can_receive_calls') : 
                ($user->can_receive_calls ?? true);
        });

        // Implement round-robin logic
        $lastAssignedAgent = Cache::get('last_assigned_agent_id', 0);
        
        foreach ($availableAgents as $agent) {
            if ($agent->id > $lastAssignedAgent) {
                Cache::put('last_assigned_agent_id', $agent->id, 3600);
                return $agent;
            }
        }

        // If no agent found after last assigned, start from beginning
        $firstAgent = $availableAgents->first();
        if ($firstAgent) {
            Cache::put('last_assigned_agent_id', $firstAgent->id, 3600);
        }

        return $firstAgent;
    }

    /**
     * Assign agent and update status
     */
    private function assignAgent(User $agent, string $sessionId): string
    {
        try {
            Log::info("Assigning agent", [
                'agent_id' => $agent->id,
                'phone_number' => $agent->phone_number,
                'sessionId' => $sessionId
            ]);

            $agent->update([
                'status' => 'busy',
                'sessionId' => $sessionId ?: uniqid()
            ]);

            $this->updateCallHistory($sessionId, ['user_id' => $agent->id]);

            Log::info("Agent assigned successfully", [
                'agent_id' => $agent->id,
                'sessionId' => $sessionId
            ]);

            return $agent->phone_number;
        } catch (Exception $e) {
            Log::error("Error assigning agent", [
                'error' => $e->getMessage(),
                'agent_id' => $agent->id,
                'sessionId' => $sessionId
            ]);
            return 0;
        }
    }

    /**
     * Generate dynamic IVR menu from database
     */
    public function generateDynamicMenu(): string
    {
        $options = IvrOption::orderBy('option_number')->get();

        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>\n";
        $response .= "<Say voice=\"{$this->config['voice']['default_voice']}\">{$this->config['messages']['welcome']}</Say>\n";
        $response .= "<GetDigits timeout=\"{$this->config['voice']['timeout']}\" finishOnKey=\"#\" callbackUrl=\"{$this->config['urls']['callback_url']}\">\n";

        $prompt = "";
        foreach ($options as $option) {
            $prompt .= "Press {$option->option_number} for {$option->description}. ";
        }

        $response .= "<Say voice=\"{$this->config['voice']['default_voice']}\" barge-in=\"true\">{$prompt}</Say>\n";
        $response .= "</GetDigits>\n";
        $response .= "<Say voice=\"{$this->config['voice']['default_voice']}\">{$this->config['messages']['no_input']}</Say>\n";
        $response .= "</Response>";

        return $response;
    }

    /**
     * Handle DTMF selection with configurable routing
     */
    public function handleDtmfSelection(string $dtmfDigits, string $callerNumber, string $sessionId): string
    {
        if (!$dtmfDigits) {
            Log::warning("No DTMF input received");
            return $this->createVoiceResponse(
                $this->config['messages']['no_input'],
                config('voice.fallback_number')
            );
        }

        Log::info("Handling IVR selection", [
            'dtmfDigits' => $dtmfDigits,
            'callerNumber' => $callerNumber,
            'sessionId' => $sessionId
        ]);

        $ivrOption = IvrOption::where('option_number', $dtmfDigits)->first();

        if (!$ivrOption) {
            Log::warning("Invalid IVR selection: {$dtmfDigits}");
            return $this->createVoiceResponse(
                $this->config['messages']['invalid_option'],
                config('voice.fallback_number')
            );
        }

        // Update call history with selection
        $this->updateCallHistoryWithSelection($sessionId, $callerNumber, $ivrOption);

        Log::info("User selected: {$ivrOption->option_number} - {$ivrOption->description}");

        // Handle special routing for "Speak to Agent" option
        if ($this->isAgentOption($ivrOption)) {
            return $this->handleAgentRequest($callerNumber, $sessionId);
        }

        // Route to specific number
        return $this->createVoiceResponse(
            "You selected {$ivrOption->description}. Connecting your call.",
            $ivrOption->forward_number ?? config('voice.default_forward_number')
        );
    }

    /**
     * Check if IVR option is for agent connection
     */
    private function isAgentOption(IvrOption $option): bool
    {
        $agentKeywords = config('voice.agent_keywords', ['agent', 'speak to agent', 'customer service']);
        
        foreach ($agentKeywords as $keyword) {
            if (stripos($option->description, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle agent request with fallback to voicemail
     */
    private function handleAgentRequest(string $callerNumber, string $sessionId): string
    {
        $agentNumber = $this->getAvailableAgent($callerNumber, $sessionId);
        
        if ($agentNumber) {
            return $this->createVoiceResponse($this->config['messages']['connecting_agent'], $agentNumber);
        }

        // No agents available - record voicemail
        return $this->createVoicemailResponse();
    }

    /**
     * Update call history with IVR selection
     */
    private function updateCallHistoryWithSelection(string $sessionId, string $callerNumber, IvrOption $ivrOption): void
    {
        $user = null;
        
        // Try to find user by phone number or forward number
        if ($ivrOption->phone_number) {
            $user = User::where('phone_number', $ivrOption->phone_number)->first();
        }
        
        if (!$user && $ivrOption->forward_number) {
            $user = User::where('phone', $ivrOption->forward_number)->first();
        }

           CallHistory::updateOrCreate(
            ['sessionId' => $sessionId],
            [
                'callerNumber' => $callerNumber,
                'ivr_option_id' => $ivrOption->id,
                'isActive' => 1,
                'user_id' => $user?->id
            ]
        );

        Log::info("Call history updated with IVR selection", [
            'sessionId' => $sessionId,
            'selectedOption' => $ivrOption->option_number,
            'user_id' => $user?->id
        ]);
    }

    /**
     * Create voice response with optional dial
     */
    private function createVoiceResponse(string $message, ?string $phoneNumber = null): string
    {
        Log::info("Generating voice response", [
            'message' => $message,
            'phoneNumber' => $phoneNumber
        ]);

        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>\n";
        $response .= "<Say voice=\"{$this->config['voice']['default_voice']}\">{$message}</Say>\n";
        
        if ($phoneNumber) {
            $recordAttr = $this->config['voice']['recording_enabled'] ? 'record="true"' : '';
            $ringbackAttr = $this->config['urls']['ringback_tone'] ? 
                'ringbackTone="' . $this->config['urls']['ringback_tone'] . '"' : '';
            
            $response .= "<Dial {$recordAttr} sequential=\"true\" {$ringbackAttr} phoneNumbers=\"{$phoneNumber}\"/>\n";
        }
        
        $response .= "</Response>";

        return $response;
    }

    /**
     * Create dial response for direct routing
     */
    private function createDialResponse(string $phoneNumber): string
    {
        $recordAttr = $this->config['voice']['recording_enabled'] ? 'record="true"' : '';
        $ringbackAttr = $this->config['urls']['ringback_tone'] ? 
            'ringbackTone="' . $this->config['urls']['ringback_tone'] . '"' : '';

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>\n" .
               "<Dial {$recordAttr} sequential=\"true\" {$ringbackAttr} phoneNumbers=\"{$phoneNumber}\"/>\n" .
               "</Response>";
    }

    /**
     * Generate dial response for outgoing calls
     */
    private function generateDialResponse(string $clientDialedNumber): string
    {
        $cleanNumber = preg_replace('/^\+/', '', trim($clientDialedNumber));
        
        $response = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $response .= '<Response>';
        $response .= '<Dial record="true" sequential="true" phoneNumbers="' . $cleanNumber . '"/>';
        $response .= '</Response>';

        Log::info("Generated outgoing dial response", [
            'clientDialedNumber' => $clientDialedNumber,
            'cleanNumber' => $cleanNumber
        ]);

        return $response;
    }

    /**
     * Create voicemail recording response
     */
    private function createVoicemailResponse(): string
    {
        Log::info("Creating voicemail response");

        $response = '<?xml version="1.0" encoding="UTF-8"?>';
        $response .= '<Response>';
        $response .= '<Say>' . $this->config['messages']['voicemail_prompt'] . '</Say>';
        $response .= '<Record finishOnKey="#" maxLength="" trimSilence="true" playBeep="true" ';
        $response .= 'callbackUrl="' . $this->config['urls']['voicemail_callback'] . '">';
        $response .= '</Record>';
        $response .= '</Response>';

        return $response;
    }

    /**
     * Update agent status
     */
    private function updateAgentStatus(string $callerNumber, string $sessionId, string $status): void
    {
        try {
            Log::info("Updating agent status", [
                'callerNumber' => $callerNumber,
                'sessionId' => $sessionId,
                'status' => $status
            ]);

            $updated = User::where('phone_number', $callerNumber)
                ->update([
                    'status' => $status,
                    'sessionId' => $sessionId,
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::info("Agent status updated successfully", [
                    'callerNumber' => $callerNumber,
                    'updated_rows' => $updated
                ]);
            } else {
                Log::warning("No agent found with phone number", [
                    'callerNumber' => $callerNumber
                ]);
            }
        } catch (Exception $e) {
            Log::error("Error updating agent status", [
                'error' => $e->getMessage(),
                'callerNumber' => $callerNumber
            ]);
        }
    }

    /**
     * Reset agent status after call completion
     */
    private function resetAgentStatus(string $sessionId): void
    {
        try {
            Log::info("Resetting agent status", ['sessionId' => $sessionId]);

            $updated = User::where('sessionId', $sessionId)
                ->update([
                    'status' => 'available',
                    'sessionId' => null,
                    'updated_at' => now()
                ]);

            Log::info("Agent status reset", [
                'sessionId' => $sessionId,
                'updated_rows' => $updated
            ]);
        } catch (Exception $e) {
            Log::error("Error resetting agent status", [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);
        }
    }

    /**
     * Update call history
     */
    private function updateCallHistory(string $sessionId, array $data): void
    {
        try {
            CallHistory::updateOrCreate(['sessionId' => $sessionId], $data);

            // Optionally broadcast event
            // if (class_exists(CallStatusUpdated::class)) {
            //     broadcast(new CallStatusUpdated($call));
            // }

            Log::info("Call history updated", [
                'sessionId' => $sessionId,
                'data' => $data
            ]);
        } catch (Exception $e) {
            Log::error("Error updating call history", [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);
        }
    }

    /**
     * Finalize call with all completion data
     */
    private function finalizeCall(Request $request, string $sessionId): void
    {
        $data = [
            'isActive' => 0,
            'recordingUrl' => $request->input('recordingUrl'),
            'durationInSeconds' => $request->input('durationInSeconds'),
            'currencyCode' => $request->input('currencyCode'),
            'amount' => $request->input('amount'),
            'hangupCause' => $request->input('hangupCause'),
            'status' => $request->input('status'),
            'dialStartTime' => $request->input('dialStartTime'),
            'dialDurationInSeconds' => $request->input('dialDurationInSeconds'),
        ];

        $this->updateCallHistory($sessionId, $data);
        
        Log::info("Call finalized", [
            'sessionId' => $sessionId,
            'data' => $data
        ]);
    }

    /**
     * Handle event callbacks
     */
    public function handleEventCallback(Request $request): array
    {
        try {
            Log::info('Received event callback', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            $payload = $request->all();

            // Update call history with event data
            CallHistory::updateOrCreate(
                ['sessionId' => $payload['sessionId'] ?? null],
                [
                    'callerNumber' => $payload['callerNumber'] ?? null,
                    'destinationNumber' => $payload['destinationNumber'] ?? null,
                    'direction' => $payload['direction'] ?? null,
                    'status' => $payload['status'] ?? null,
                    'isActive' => $payload['isActive'] ?? null,
                    'callStartTime' => $payload['callStartTime'] ?? null,
                    'durationInSeconds' => $payload['durationInSeconds'] ?? null,
                    'amount' => $payload['amount'] ?? null,
                    'currencyCode' => $payload['currencyCode'] ?? null,
                    'callerCountryCode' => $payload['callerCountryCode'] ?? null,
                    'callerCarrierName' => $payload['callerCarrierName'] ?? null,
                    'dialStartTime' => $payload['dialStartTime'] ?? null,
                    'dialDurationInSeconds' => $payload['dialDurationInSeconds'] ?? null,
                    'clientDialedNumber' => $payload['clientDialedNumber'] ?? null,
                    'recordingUrl' => $payload['recordingUrl'] ?? null,
                    'hangupCause' => $payload['hangupCause'] ?? null,
                    'callSessionState' => $payload['callSessionState'] ?? null,
                    'lastBridgeHangupCause' => $payload['lastBridgeHangupCause'] ?? null,
                ]
            );

            // Check if call is completed and reset agent status
            $completed = strtolower($payload['callSessionState'] ?? '') === 'completed' || 
                        strtolower($payload['status'] ?? '') === 'completed';

            if ($completed && isset($payload['sessionId'])) {
                $this->resetAgentStatus($payload['sessionId']);
            }

            return ['status' => 'success'];
        } catch (Exception $e) {
            Log::error("Error in event callback", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['error' => 'Internal Server Error'];
        }
    }

    /**
     * Transfer call to another agent
     */
    public function transferCall(int $agentId, string $callId): array
    {
        try {
            $agent = User::find($agentId);

            if (!$agent || ($agent->isInCall ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Agent not found or is busy'
                ];
            }

            $response = $this->africastalking->voice()->transfer([
                'callId' => $callId,
                'destination' => $agent->phone_number,
            ]);

            Log::info('Call transferred successfully', [
                'agentId' => $agentId,
                'callId' => $callId,
                'response' => $response
            ]);

            return [
                'success' => true,
                'message' => 'Call transferred successfully',
                'data' => $response
            ];
        } catch (Exception $e) {
            Log::error('Error transferring call', [
                'error' => $e->getMessage(),
                'agentId' => $agentId,
                'callId' => $callId
            ]);

            return [
                'success' => false,
                'message' => 'Error transferring call',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate capability tokens for WebRTC
     */
    public function generateTokens(?array $userIds = null): array
    {

        
        $apiKey = $this->config['africastalking']['api_key'];
        $username = $this->config['africastalking']['username'];
        $phoneNumber = $this->config['africastalking']['phone'];

        if (!$username || !$apiKey) {
            throw new Exception('Africa\'s Talking credentials are missing');
        }

        $users = $userIds ? User::whereIn('id', $userIds)->get() : User::all();
        $updatedTokens = [];
        $failedUpdates = [];

        foreach ($users as $user) {
            try {
                // Ensure unique client name
                if (empty($user->username)) {
                    $user->username = 'client_' . $user->id . '_' . substr(md5(uniqid()), 0, 6);
                    $user->save();
                }

                $clientName = str_replace(' ', '', $user->username);
                $incoming = $user->can_receive_calls ?? true;
                $outgoing = $user->can_call ?? true;

                $payload = [
                    'username' => $username,
                    'clientName' => $clientName,
                    'phoneNumber' => $phoneNumber,
                    'incoming' => $incoming ? "true" : "false",
                    'outgoing' => $outgoing ? "true" : "false",
                    'lifeTimeSec' => "86400"
                ];

                $response = $this->makeTokenRequest($payload, $apiKey);

                if (!isset($response['token'])) {
                    throw new Exception($response['message'] ?? 'Unknown API error');
                }

                $user->updateOrFail(['token' => $response['token']]);

                Log::info("Token updated successfully for user {$user->id}");

                $updatedTokens[] = [
                    'user_id' => $user->id,
                    'token' => $response['token'],
                    'clientName' => $response['clientName'] ?? $clientName,
                    'incoming' => $response['incoming'] ?? null,
                    'outgoing' => $response['outgoing'] ?? null,
                    'lifeTimeSec' => $response['lifeTimeSec'] ?? null,
                    'message' => $response['message'] ?? null,
                    'success' => $response['success'] ?? false
                ];
            } catch (Exception $e) {
                Log::error("Token generation failed for user {$user->id}: " . $e->getMessage());

                $failedUpdates[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'updatedTokens' => $updatedTokens,
            'failedUpdates' => $failedUpdates,
            'totalUpdated' => count($updatedTokens),
            'totalFailed' => count($failedUpdates),
        ];
    }

    /**
     * Make token request to Africa's Talking API
     */
    private function makeTokenRequest(array $payload, string $apiKey): array
    {
        $url = 'https://webrtc.africastalking.com/capability-token/request';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apiKey: ' . $apiKey,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        return json_decode($response, true) ?? [];
    }

    /**
     * Upload media file for voice prompts
     */
    public function uploadMediaFile(string $fileUrl, ?string $phoneNumber = null): array
    {
        $phoneNumber = $phoneNumber ?? $this->config['africastalking']['phone'];

        if (!$phoneNumber) {
            throw new Exception('Phone number is required for media upload');
        }

        try {
            $voice = $this->africastalking->voice();
            
            $result = $voice->uploadMediaFile([
                "phoneNumber" => $phoneNumber,
                "url" => $fileUrl
            ]);

            Log::info('Media file uploaded successfully', [
                'fileUrl' => $fileUrl,
                'phoneNumber' => $phoneNumber,
                'result' => $result
            ]);

            return [
                'success' => true,
                'message' => 'Media file uploaded successfully',
                'data' => $result
            ];
        } catch (Exception $e) {
            Log::error('Error uploading media file', [
                'error' => $e->getMessage(),
                'fileUrl' => $fileUrl,
                'phoneNumber' => $phoneNumber
            ]);

            return [
                'success' => false,
                'message' => 'Error uploading media file',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get queued calls
     */
    public function getQueuedCalls(string $status = 'queued'): array
    {
        try {
            $queuedCalls = CallQueue::where('status', $status)->get();

            return [
                'success' => true,
                'data' => $queuedCalls
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch queued calls', [
                'error' => $e->getMessage(),
                'status' => $status
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch queued calls',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Dequeue a call when agent is available
     */
    public function dequeueCall(string $callId): array
    {
        try {
            $nextQueuedCall = CallQueue::where('status', 'queued')
                ->oldest()
                ->first();

            if (!$nextQueuedCall) {
                return [
                    'success' => true,
                    'message' => 'No calls in the queue'
                ];
            }

            $nextQueuedCall->status = 'answered';
            $nextQueuedCall->save();

            $voice = $this->africastalking->voice();
            
            $response = $voice->dequeue([
                'callId' => $callId,
                'phoneNumber' => $nextQueuedCall->phone_number,
            ]);

            Log::info('Call dequeued successfully', [
                'callId' => $callId,
                'phoneNumber' => $nextQueuedCall->phone_number
            ]);

            return [
                'success' => true,
                'message' => 'Call dequeued successfully',
                'data' => $response
            ];
        } catch (Exception $e) {
            Log::error('Error dequeuing call', [
                'error' => $e->getMessage(),
                'callId' => $callId
            ]);

            return [
                'success' => false,
                'message' => 'Error dequeuing call',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if all agents are busy
     */
    public function areAllAgentsBusy(): bool
    {
        $busyAgents = User::where('status', 'busy')->count();
        $maxAgents = $this->config['voice']['max_agents'];

        return $busyAgents >= $maxAgents;
    }

    /**
     * Get call statistics for date range
     */
    public function getCallStats(?array $dateRange = null, ?int $userId = null): array
    {
        if (!$dateRange) {
            $dateRange = [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ];
        }

        if ($userId) {
            $user = User::find($userId);
            return $this->callStatsService->getAgentStats($user, $dateRange);
        }

        return $this->callStatsService->generateCallSummaryReport([
            'startDate' => $dateRange[0]->format('Y-m-d'),
            'endDate' => $dateRange[1]->format('Y-m-d'),
        ]);
    }

    /**
     * Get agent list summary with filters
     */
    public function getAgentListSummary(array $filters = []): array
    {
        $dateRange = $this->parseDateFilter($filters);
        $agents = User::all();
        $results = [];

        foreach ($agents as $agent) {
            $results[] = $this->callStatsService->getAgentStats($agent, $dateRange);
        }

        return $results;
    }

    /**
     * Parse date filter from request
     */
    private function parseDateFilter(array $filters): array
    {
        $callDate = $filters['call_date'] ?? null;
        $customDate = $filters['custom_date'] ?? null;
        $customStartDate = $filters['custom_start_date'] ?? null;
        $customEndDate = $filters['custom_end_date'] ?? null;

        switch ($callDate) {
            case 'today':
                return [Carbon::today(), Carbon::today()->endOfDay()];
            case 'current_week':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'last_week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            case 'current_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'current_year':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            case 'custom_date':
                $date = Carbon::parse($customDate);
                return [$date->startOfDay(), $date->endOfDay()];
            case 'custom_range':
                return [Carbon::parse($customStartDate)->startOfDay(), Carbon::parse($customEndDate)->endOfDay()];
            default:
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
        }
    }

    /**
     * Fetch call history with pagination and filters
     */
    public function fetchCallHistory(array $filters = []): array
    {
        try {
            $perPage = $filters['per_page'] ?? 15;
            $page = $filters['page'] ?? 1;
            $search = $filters['search'] ?? null;
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortDesc = $filters['sort_desc'] ?? true;

            $query = CallHistory::with('agent', 'ivrOption');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('lastBridgeHangupCause', 'like', "%{$search}%")
                        ->orWhere('callerNumber', 'like', "%{$search}%")
                        ->orWhere('clientDialedNumber', 'like', "%{$search}%");
                });
            }

            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

            $callHistories = $query->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'data' => $callHistories
            ];
        } catch (Exception $e) {
            Log::error("Error fetching call history: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to fetch call history',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate comprehensive call summary report
     */
    public function generateCallSummaryReport(array $filters = []): array
    {
        return $this->callStatsService->generateCallSummaryReport($filters);
    }
}