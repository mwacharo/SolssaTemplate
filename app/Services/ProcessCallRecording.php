<?php

namespace App\Jobs;

use App\Models\CallTranscript;
use App\Services\SpeechService;
use App\Services\AnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCallRecording implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public string $recordingUrl;
    public ?string $callId;
    public ?int $userId;

    public $tries = 3;
    public $backoff = 60; // seconds

    public function __construct(string $recordingUrl, ?string $callId = null, ?int $userId = null)
    {
        $this->recordingUrl = $recordingUrl;
        $this->callId = $callId;
        $this->userId = $userId;
    }

    public function handle(SpeechService $speechService, AnalysisService $analysisService)
    {
        Log::info('Processing recording', ['url' => $this->recordingUrl, 'call_id' => $this->callId]);

        // create initial DB row
        $transcriptRow = CallTranscript::create([
            'call_id' => $this->callId,
            'user_id' => $this->userId,
            'recording_url' => $this->recordingUrl,
        ]);

        // 1) Download and transcribe
        $transcriptText = $speechService->transcribeFromUrl($this->recordingUrl);

        if (empty($transcriptText)) {
            Log::warning('Transcription returned empty', ['recording' => $this->recordingUrl]);
            $transcriptText = '';
        }

        // 2) Run analysis (intent/sentiment/fulfillment/customer service rating)
        $analysis = $analysisService->analyzeTranscript($transcriptText);

        // 3) Save
        $transcriptRow->update([
            'transcript' => $transcriptText,
            'sentiment' => $analysis['sentiment'] ?? null,
            'fulfillment_score' => $analysis['fulfillment_score'] ?? null,
            'cs_rating' => $analysis['cs_rating'] ?? null,
            'analysis' => $analysis,
            'processed_at' => now(),
        ]);

        // optionally: event dispatch or webhook call to notify CRM/UI
        Log::info('Processing complete', ['id' => $transcriptRow->id]);
    }
}
