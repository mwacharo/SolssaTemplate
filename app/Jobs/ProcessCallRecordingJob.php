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

class ProcessCallRecordingJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public string $recordingUrl;
    public ?string $callId;
    public ?int $userId;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(string $recordingUrl, ?string $callId = null, ?int $userId = null)
    {
        $this->recordingUrl = $recordingUrl;
        $this->callId = $callId;
        $this->userId = $userId;
    }

    public function handle(SpeechService $speechService, AnalysisService $analysisService)
    {
        Log::info('Processing recording', [
            'url' => $this->recordingUrl,
            'call_id' => $this->callId,
        ]);

        // 1. Create initial DB row
        $transcriptRow = CallTranscript::create([
            'call_id' => $this->callId,
            'user_id' => $this->userId,
            'recording_url' => $this->recordingUrl,
        ]);

        // 2. Load and transcribe
        $transcriptText = $speechService->transcribeFromUrl($this->recordingUrl);

        if (empty($transcriptText)) {
            Log::warning('Transcription returned empty', ['recording' => $this->recordingUrl]);
            $transcriptText = '';
        }

        // 3. Analysis (intent/sentiment/fulfillment/customer service rating)
        $analysis = $analysisService->analyzeTranscript($transcriptText);

        // 4. Save
        $transcriptRow->update([
            'transcript' => $transcriptText,
            'sentiment' => $analysis['sentiment'] ?? null,
            'fulfillment_score' => $analysis['fulfillment_score'] ?? null,
            'cs_rating' => $analysis['cs_rating'] ?? null,
            'analysis' => $analysis,
            'processed_at' => now(),
        ]);


        Log::info('Call recording processed', [
            'call_id' => $this->callId,
            'user_id' => $this->userId,
            'transcript_length' => strlen($transcriptText),
            'fulltranscript' => $transcriptText,
            'sentiment' => $analysis['sentiment'] ?? 'N/A',
            'fulfillment_score' => $analysis['fulfillment_score'] ?? 'N/A',
            'cs_rating' => $analysis['cs_rating'] ?? 'N/A',
        ]);

        // Optionally: event dispatch or webhook call to notify CRM/UI
        Log::info('Processing complete', ['id' => $transcriptRow->id]);
    }
}
