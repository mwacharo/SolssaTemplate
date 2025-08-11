<?php

namespace App\Jobs;

use App\Models\CallHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DownloadCallRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callId;
    protected $recordingUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(int $callId, string $recordingUrl)
    {
        $this->callId = $callId;
        $this->recordingUrl = $recordingUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::debug("Starting DownloadCallRecordingJob for Call ID {$this->callId} with URL {$this->recordingUrl}");

        try {
            $call = CallHistory::find($this->callId);

            if (!$call) {
                Log::warning("🚨 CallHistory not found for ID {$this->callId}");
                return;
            }

            // Generate a unique filename
            $fileName = "call_{$this->callId}_" . now()->timestamp . ".mp3";
            $filePath = "call_recordings/{$fileName}";
            Log::debug("Generated file name: {$fileName}, file path: {$filePath}");

            // Fetch the audio file from Africa's Talking
            Log::debug("Sending HTTP GET request to {$this->recordingUrl}");
            $response = Http::timeout(15)->get($this->recordingUrl);

            Log::debug("HTTP response status: {$response->status()}");

            if ($response->successful()) {
                Log::debug("HTTP request successful. Storing file to disk 'public' at {$filePath}");
                Storage::disk('public')->put($filePath, $response->body());

                // Update DB record
                $call->update([
                    'download_status' => 'downloaded',
                    'recordingUrl' => asset("storage/{$filePath}"),
                ]);

                Log::info("✅ Downloaded & saved call recording for Call ID {$this->callId}");
            } else {
                Log::error("❌ Failed to fetch recording for Call ID {$this->callId} (HTTP {$response->status()})");
            }
        } catch (\Throwable $e) {
            Log::error("🚨 Error downloading call recording for Call ID {$this->callId}: " . $e->getMessage());
        }
    }
    //         $call = CallHistory::find($this->callId);

    //         if (!$call) {
    //             Log::warning("🚨 CallHistory not found for ID {$this->callId}");
    //             return;
    //         }

    //         // Generate a unique filename
    //         $fileName = "call_{$this->callId}_" . now()->timestamp . ".mp3";
    //         $filePath = "call_recordings/{$fileName}";

    //         // Fetch the audio file from Africa's Talking
    //         $response = Http::timeout(30)->get($this->recordingUrl);

    //         if ($response->successful()) {
    //             // Store in DigitalOcean Spaces
    //             $uploaded = Storage::disk('spaces')->put($filePath, $response->body(), 'public');

    //             if ($uploaded) {
    //                 // Generate the public URL
    //                 $publicUrl = Storage::disk('spaces')->url($filePath);

    //                 // Update DB record
    //                 $call->update([
    //                     'download_status' => 'downloaded',
    //                     'recordingUrl' => $publicUrl,
    //                     'file_path' => $filePath, // Store the path for potential deletion later
    //                 ]);

    //                 Log::info("✅ Downloaded & saved call recording to DigitalOcean Spaces for Call ID {$this->callId}");
    //                 Log::info("📁 File URL: {$publicUrl}");
    //             } else {
    //                 Log::error("❌ Failed to upload recording to DigitalOcean Spaces for Call ID {$this->callId}");
                    
    //                 // Update status to failed
    //                 $call->update(['download_status' => 'failed']);
    //             }
    //         } else {
    //             Log::error("❌ Failed to fetch recording for Call ID {$this->callId} (HTTP {$response->status()})");
                
    //             // Update status to failed
    //             $call->update(['download_status' => 'failed']);
    //         }
    //     } catch (\Throwable $e) {
    //         Log::error("🚨 Error downloading call recording for Call ID {$this->callId}: " . $e->getMessage());
            
    //         // Update status to failed if call exists
    //         if (isset($call)) {
    //             $call->update(['download_status' => 'failed']);
    //         }
    //     }
    // }
}
