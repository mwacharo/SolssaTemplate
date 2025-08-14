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
    public ?int $callId;
    public ?int $userId;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(string $recordingUrl, ?string $callId = null, ?int $userId = null)
    {
        Log::debug('[JOB:CONSTRUCT] Received params', [
            'recordingUrl' => $recordingUrl,
            'callId_raw' => $callId,
            'callId_type' => gettype($callId),
            'userId' => $userId,
        ]);

        $this->recordingUrl = $recordingUrl;
        $this->callId = !empty($callId) ? (int) $callId : null;
        $this->userId = $userId;

        Log::debug('[JOB:CONSTRUCT] Parsed values', [
            'callId_final' => $this->callId,
            'userId_final' => $this->userId,
        ]);
    }

    public function handle(SpeechService $speechService, AnalysisService $analysisService)
    {
        Log::info('[JOB:HANDLE] Start processing', [
            'recordingUrl' => $this->recordingUrl,
            'callId' => $this->callId,
            'userId' => $this->userId,
        ]);

        if (is_null($this->callId)) {
            Log::error('[JOB:HANDLE] callId is NULL - transcript will not be linked correctly!');
        }

        // Step 1: Insert DB row
        try {
            $transcriptRow = CallTranscript::create([
                'call_history_id' => $this->callId,
                'user_id' => $this->userId,
                'recording_url' => $this->recordingUrl,
            ]);

            Log::debug('[JOB:HANDLE] Transcript row created', [
                'db_id' => $transcriptRow->id,
                'db_call_history_id' => $transcriptRow->call_history_id,
            ]);
        } catch (\Exception $e) {
            Log::error('[JOB:HANDLE] Failed to insert transcript row', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return;
        }

        // Step 2: Transcription
        $transcriptText = $speechService->transcribeFromUrl($this->recordingUrl);
        if (empty($transcriptText)) {
            Log::warning('[JOB:HANDLE] Transcription empty', ['url' => $this->recordingUrl]);
            $transcriptText = '';
        }

        // Step 3: Analysis
        $analysis = $analysisService->analyzeTranscript($transcriptText);

        // Step 4: Update DB row
        $transcriptRow->update([
            'transcript' => $transcriptText,
            'sentiment' => $analysis['sentiment'] ?? null,
            'fulfillment_score' => $analysis['fulfillment_score'] ?? null,
            'cs_rating' => $analysis['cs_rating'] ?? null,
            'analysis' => $analysis,
            'processed_at' => now(),
        ]);

        Log::info('[JOB:HANDLE] Processing complete', [
            'transcript_id' => $transcriptRow->id,
            'final_callId' => $transcriptRow->call_history_id,
        ]);
    }
}
