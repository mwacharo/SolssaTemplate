<?php
/**
 * Laravel AI-Powered Call Center (Africa's Talking + Whisper + GPT + TTS)
 * -----------------------------------------------------------------------
 * This scaffold wires: inbound & outbound calls (Africa's Talking Voice),
 * transcription (Whisper), reasoning (GPT), speech (TTS), and CRM/Orders lookup.
 *
 * Files included in this single snippet:
 * - app/Services/AIContactCenter.php               (core orchestrator)
 * - app/Http/Controllers/VoiceController.php       (webhook + outbound endpoints)
 * - app/Jobs/ProcessVoiceRecording.php             (downloads recording -> STT -> GPT -> TTS)
 * - app/Services/NLU/PromptBuilder.php             (domain prompts)
 * - app/Services/Telephony/AfricasTalkingClient.php(voice actions + call)
 * - routes/api.php                                 (routes)
 * - config/ai_cc.php                               (config)
 * - .env.example                                   (env keys)
 *
 * Assumptions:
 * - You already have an Order model with phone linkage.
 * - Africa's Talking sends Voice callbacks to /api/voice/callback with fields:
 *     sessionId, isActive ("1" when call is ongoing), callerNumber, destinationNumber,
 *     recordingUrl (after RECORD), dtmfDigits (after GET_DIGITS), etc.
 * - You prefer JSON voice actions (AT supports XML or JSON). We emit JSON.
 * - For state, we use cache()->remember/put keyed by sessionId (use Redis in prod).
 */

// ============================================================================
// app/Services/AIContactCenter.php
// ============================================================================

namespace App\Services;

use App\Jobs\ProcessVoiceRecording;
use App\Models\Order;
use App\Services\NLU\PromptBuilder;
use App\Services\Telephony\AfricasTalkingClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIContactCenter
{
    public function __construct(
        protected AfricasTalkingClient $at,
        protected PromptBuilder $prompter,
    ) {}

    /**
     * Start an outbound AI call for the most recent 'new' order of a customer.
     */
    public function placeOutboundCallForPhone(string $msisdn): array
    {
        $msisdn = $this->normalizeMsisdn($msisdn);
        $order  = $this->latestNewOrderForPhone($msisdn);

        $meta = [
            'type' => 'outbound',
            'msisdn' => $msisdn,
            'order' => $order?->only(['id','order_number','status','item_summary','delivery_window','delivery_address']),
        ];

        return $this->at->call($msisdn, $meta);
    }

    /** Handle Africa's Talking Voice webhook. */
    public function handleVoiceWebhook(array $payload): array
    {
        $sessionId = Arr::get($payload, 'sessionId');
        $isActive  = Arr::get($payload, 'isActive');
        $caller    = Arr::get($payload, 'callerNumber');
        $digits    = Arr::get($payload, 'dtmfDigits');
        $recording = Arr::get($payload, 'recordingUrl');

        $state = Cache::get($this->key($sessionId), [
            'step' => 'greet',
            'context' => [
                'caller' => $caller,
                'order'  => $this->latestNewOrderForPhone($caller)?->only(['id','order_number','status','item_summary','delivery_window','delivery_address']),
            ],
        ]);

        // If call just ended
        if ($isActive === '0') {
            Log::info('☎️ Call ended', compact('sessionId'));
            Cache::forget($this->key($sessionId));
            return $this->at->hangup();
        }

        // If we received a recording URL (after RECORD)
        if ($recording) {
            ProcessVoiceRecording::dispatch($recording, $sessionId, $state['context']);
            // While we process, acknowledge with a short prompt and return to wait.
            return $this->at->say([
                'text' => 'Give me a second while I check that for you.',
                'voice' => config('ai_cc.tts.voice'),
            ]);
        }

        // Basic state machine
        switch ($state['step']) {
            case 'greet':
                $state['step'] = 'confirm_availability';
                Cache::put($this->key($sessionId), $state, now()->addMinutes(10));
                $orderText = $this->orderOneLiner($state['context']['order']);
                return $this->at->response([
                    $this->at->actionSay("Hello, this is Boxleo Courier. {$orderText}"),
                    $this->at->actionGetDigits(
                        prompt: 'Press 1 if you will be available for delivery today, 2 to reschedule, or 9 to talk to a human agent.',
                        timeoutSeconds: 6
                    ),
                    $this->at->actionRecord('Please briefly say your request after the beep, then press hash.'),
                ]);

            case 'confirm_availability':
                if ($digits === '1') {
                    $state['step'] = 'upsell';
                    Cache::put($this->key($sessionId), $state, now()->addMinutes(10));
                    return $this->at->say(['text' => 'Great. Your delivery is confirmed for today.']);
                } elseif ($digits === '2') {
                    $state['step'] = 'reschedule_offer';
                    Cache::put($this->key($sessionId), $state, now()->addMinutes(10));
                    return $this->at->say(['text' => 'No problem. I can reschedule. Tomorrow morning between 9 and 11 AM is available.']);
                } elseif ($digits === '9') {
                    $state['step'] = 'handoff';
                    Cache::put($this->key($sessionId), $state, now()->addMinutes(10));
                    return $this->at->enqueueHuman();
                }
                // No digits or different input -> ask again
                return $this->at->say(['text' => 'Sorry, I did not catch that.']);

            case 'reschedule_offer':
                // After offering slot, record their spoken confirmation
                return $this->at->record('Please say yes to confirm tomorrow morning, or propose a different time.');

            case 'upsell':
                $state['step'] = 'wrap';
                Cache::put($this->key($sessionId), $state, now()->addMinutes(10));
                return $this->at->response([
                    $this->at->actionGetDigits('Press 1 to add delivery insurance for two hundred Kenya shillings, or 2 to skip.'),
                ]);

            case 'wrap':
                return $this->at->say(['text' => 'Thanks for your time. You will receive an SMS confirmation shortly. Goodbye.']);

            case 'handoff':
                return $this->at->enqueueHuman();
        }

        return $this->at->say(['text' => 'Goodbye.']);
    }

    protected function key(string $sessionId): string
    { return 'voice:session:'.$sessionId; }

    protected function latestNewOrderForPhone(string $msisdn): ?Order
    {
        $msisdn = $this->normalizeMsisdn($msisdn);
        return Order::where(function($q) use($msisdn) {
                $q->where('customer_phone', $msisdn)
                  ->orWhere('customer_phone', 'like', '%'.ltrim($msisdn, '+').'%');
            })
            ->where('status', 'new')
            ->latest('created_at')
            ->first();
    }

    protected function orderOneLiner(?array $order): string
    {
        if (!$order) return 'We detected a recent order that needs your confirmation.';
        $ref   = $order['order_number'] ?? ('#'.$order['id']);
        $item  = $order['item_summary'] ?? 'your package';
        $when  = $order['delivery_window'] ?? 'today';
        return "This is about order {$ref} for {$item}, scheduled {$when}.";
    }

    protected function normalizeMsisdn(string $raw): string
    {
        $raw = trim($raw);
        if (str_starts_with($raw, '0'))   return '+254'.substr($raw, 1);
        if (str_starts_with($raw, '254')) return '+'.$raw;
        if (!str_starts_with($raw, '+'))  return '+'.$raw;
        return $raw;
    }
}

// ============================================================================
// app/Services/Telephony/AfricasTalkingClient.php
// ============================================================================

namespace App\Services\Telephony;

use Illuminate\Support\Facades\Http;

class AfricasTalkingClient
{
    public function __construct() {}

    // Place a call (outbound)
    public function call(string $msisdn, array $metadata = []): array
    {
        $username = config('ai_cc.at.username');
        $apiKey   = config('ai_cc.at.api_key');
        $from     = config('ai_cc.at.voice_number');

        // AT Voice: POST https://api.africastalking.com/call
        // Headers: apiKey, Accept: application/json
        return Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->post('https://api.africastalking.com/call', [
                'username' => $username,
                'from' => $from,
                'to'   => $msisdn,
                'clientRequestId' => $metadata['order']['order_number'] ?? null,
            ])->json();
    }

    // ------- Voice Action Helpers (JSON) -------

    public function response(array $actions): array
    { return ['actions' => $actions]; }

    public function actionSay(string $text, string $voice = null): array
    { return ['action' => 'Say', 'text' => $text, 'voice' => $voice ?? config('ai_cc.tts.voice')]; }

    public function say(array $opts): array
    { return $this->response([ $this->actionSay($opts['text'], $opts['voice'] ?? null) ]); }

    public function actionGetDigits(string $prompt, int $timeoutSeconds = 6): array
    { return ['action' => 'GetDigits', 'say' => $prompt, 'timeout' => $timeoutSeconds]; }

    public function actionRecord(string $prompt): array
    { return ['action' => 'Record', 'say' => $prompt, 'finishOnKey' => '#']; }

    public function record(string $prompt): array
    { return $this->response([ $this->actionRecord($prompt) ]); }

    public function enqueueHuman(): array
    {
        // Route to your support queue / agent number(s)
        return $this->response([
            ['action' => 'Say', 'text' => 'Transferring you to a human agent.'],
            ['action' => 'Dial', 'number' => config('ai_cc.at.agent_queue_number')],
        ]);
    }

    public function hangup(): array
    { return $this->response([ ['action' => 'Hangup'] ]); }
}

// ============================================================================
// app/Services/NLU/PromptBuilder.php
// ============================================================================

namespace App\Services\NLU;

class PromptBuilder
{
    public function buildSystemPrompt(array $ctx): string
    {
        $order = $ctx['order'] ?? [];
        $ref   = $order['order_number'] ?? ($order['id'] ?? '');
        $item  = $order['item_summary'] ?? 'the customer\'s package';
        $when  = $order['delivery_window'] ?? 'today';

        return trim(<<<PROMPT
You are an AI voice agent for Boxleo Courier & Fulfillment in Kenya.
Goal: confirm delivery or reschedule for the most recent NEW order, offer optional insurance (KES 200), be concise & professional.
Order context: ref {$ref}, item {$item}, scheduled {$when}.
Rules:
- Greet politely, reference the order.
- If caller is available today, confirm.
- If not available, offer slots: tomorrow 9–11am or 2–4pm. Confirm choice.
- If user asks something else, answer briefly using general courier knowledge and ask a clarifying follow-up.
- Keep replies 1–2 sentences.
- If uncertain, say you will have a human follow up and end politely.
PROMPT);
    }
}

// ============================================================================
// app/Jobs/ProcessVoiceRecording.php
// ============================================================================

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\NLU\PromptBuilder;

class ProcessVoiceRecording implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $recordingUrl,
        public string $sessionId,
        public array $context
    ) {}

    public function handle(PromptBuilder $prompter): void
    {
        // 1) Download audio
        $audio = Http::get($this->recordingUrl)->body();
        $path  = "recordings/{$this->sessionId}.wav";
        Storage::put($path, $audio);

        // 2) Whisper STT
        $transcript = $this->whisperTranscribe(Storage::path($path));

        // 3) GPT intent/response
        $reply = $this->gptReply($prompter->buildSystemPrompt($this->context), $transcript);

        // 4) TTS synth
        $audioPath = $this->ttsSynthesize($reply);

        // 5) Notify telephony layer to PLAY (optional: push to a pub/sub or set cache flag)
        Log::info('🗣️ AI reply ready', [
            'sessionId' => $this->sessionId,
            'transcript' => $transcript,
            'reply' => $reply,
            'audio' => $audioPath,
        ]);
    }

    protected function whisperTranscribe(string $filePath): string
    {
        $apiKey = config('ai_cc.openai.key');
        $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->asMultipart()->post('https://api.openai.com/v1/audio/transcriptions', [
                ['name' => 'file', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
                ['name' => 'model', 'contents' => 'whisper-1'],
                ['name' => 'response_format', 'contents' => 'text'],
                ['name' => 'temperature', 'contents' => '0'],
            ]);
        return trim($resp->body());
    }

    protected function gptReply(string $systemPrompt, string $userText): string
    {
        $apiKey = config('ai_cc.openai.key');
        $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'temperature' => 0.3,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userText],
                ],
            ])->json();
        return $resp['choices'][0]['message']['content'] ?? 'Thank you. A human agent will follow up.';
    }

    protected function ttsSynthesize(string $text): string
    {
        $apiKey = config('ai_cc.openai.key');
        $voice  = config('ai_cc.tts.voice');
        $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/audio/speech', [
                'model' => 'gpt-4o-mini-tts',
                'voice' => $voice,
                'input' => $text,
                'format' => 'wav',
            ]);
        $audioBin = $resp->body();
        $out = "tts/{$this->sessionId}-reply.wav";
        Storage::put($out, $audioBin);
        return Storage::path($out);
    }
}

// ============================================================================
// app/Http/Controllers/VoiceController.php
// ============================================================================

namespace App\Http\Controllers;

use App\Services\AIContactCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoiceController extends Controller
{
    public function __construct(protected AIContactCenter $cc) {}

    // Africa's Talking Voice webhook (inbound + outbound call flow)
    public function callback(Request $request)
    {
        $payload = $request->all();
        Log::info('📞 AT Voice Callback', $payload);
        $resp = $this->cc->handleVoiceWebhook($payload);
        return response()->json($resp);
    }

    // Optional: AT Voice event delivery receipts
    public function events(Request $request)
    {
        Log::info('📟 AT Voice Event', $request->all());
        return response()->json(['status' => 'ok']);
    }

    // Trigger an outbound call for a phone
    public function outbound(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $res = $this->cc->placeOutboundCallForPhone($request->string('phone'));
        return response()->json($res);
    }
}

// ============================================================================
// routes/api.php (excerpt)
// ============================================================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VoiceController;

Route::post('/voice/callback', [VoiceController::class, 'callback']);
Route::post('/voice/events',   [VoiceController::class, 'events']);
Route::post('/voice/outbound', [VoiceController::class, 'outbound']);

// ============================================================================
// config/ai_cc.php
// ============================================================================

return [
    'at' => [
        'username' => env('AT_USERNAME'),
        'api_key' => env('AT_API_KEY'),
        'voice_number' => env('AT_VOICE_NUMBER'),
        'agent_queue_number' => env('AT_AGENT_QUEUE_NUMBER', null),
    ],
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
    'tts' => [
        'voice' => env('TTS_VOICE', 'alloy'),
    ],
];

// ============================================================================
// .env.example (keys you need)
// ============================================================================

AT_USERNAME=your_at_username
AT_API_KEY=your_at_api_key
AT_VOICE_NUMBER=+2547XXXXXXX
AT_AGENT_QUEUE_NUMBER=+2547YYYYYYY

OPENAI_API_KEY=sk-xxxx
TTS_VOICE=alloy

VOICE_WEBHOOK_URL=https://your-domain.ngrok-free.app/api/voice/callback
VOICE_EVENTS_URL=https://your-domain.ngrok-free.app/api/voice/events

/**
 * How it works (runtime):
 * 1) Outbound: POST /api/voice/outbound { phone: "+2547..." }
 *    -> AT dials customer, your /voice/callback drives the IVR + Record.
 * 2) Inbound: AT forwards incoming call to /voice/callback.
 * 3) After each Record, ProcessVoiceRecording job runs: download -> Whisper -> GPT -> TTS.
 *    You can extend VoiceController to poll cache for synthesized reply and then PLAY it.
 *
 * Notes:
 * - For full duplex (near-real-time), move to streaming STT + streaming TTS.
 * - For production, store session state in Redis, and secure webhooks by IP or signature.
 * - Map Orders to phone numbers and sanitize MSISDNs consistently.
 * - Replace action names/fields to match the latest Africa's Talking Voice JSON if needed.
 */
