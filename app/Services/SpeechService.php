<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SpeechService
{
    public function transcribeFromUrl(string $url): ?string
    {
        // 1. Download the file to storage/tmp
        $tmpPath = storage_path('app/tmp/' . Str::random(12) . '.mp3');

        try {
            $resp = Http::timeout(30)->get($url);
            if ($resp->failed()) {
                Log::error('Failed to download audio', ['url' => $url, 'status' => $resp->status()]);
                return null;
            }
            // ensure dir exists
            \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($tmpPath));
            file_put_contents($tmpPath, $resp->body());
        } catch (\Exception $e) {
            Log::error('Error downloading audio', ['error' => $e->getMessage()]);
            return null;
        }

        // 2. Send to OpenAI / Whisper (example)
        $transcript = $this->transcribeWithOpenAI($tmpPath);

        // cleanup
        @unlink($tmpPath);

        return $transcript;
    }

    protected function transcribeWithOpenAI(string $filePath): ?string
    {
        $openAiKey = env('OPENAI_API_KEY');
        if (!$openAiKey) {
            Log::error('OpenAI key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$openAiKey}",
            ])->attach('file', fopen($filePath, 'r'))
              ->asMultipart()
              ->post('https://api.openai.com/v1/audio/transcriptions', [
                  'model' => 'gpt-4o-transcribe', // example — replace with the model your plan supports
                  // 'language' => 'en',
              ]);

            if ($response->successful()) {
                $json = $response->json();
                // OpenAI returns 'text' in some variants, check the actual response shape
                return $json['text'] ?? ($json['transcript'] ?? null);
            } else {
                Log::error('OpenAI transcription failed', ['status' => $response->status(), 'body' => $response->body()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenAI transcription error', ['err' => $e->getMessage()]);
            return null;
        }
    }
}
