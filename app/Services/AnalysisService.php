<?php
namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalysisService
{
    // primary method
    public function analyzeTranscript(?string $text): array
    {
        $text = $text ?? '';

        // quick heuristics
        $sentiment = $this->estimateSentiment($text);
        $intent = $this->extractIntent($text);
        $fulfillmentScore = $this->estimateFulfillmentProbability($text, $intent, $sentiment);
        $csRating = $this->rateCustomerService($text);

        $analysis = [
            'sentiment' => $sentiment,
            'intent' => $intent,
            'keywords' => $this->extractKeywords($text),
            'fulfillment_score' => $fulfillmentScore,
            'cs_rating' => $csRating,
            'raw_length' => strlen($text),
        ];

        // Optional: call an LLM for a richer structured analysis if configured
        if (config('services.openai.key') && config('services.openai.use_gpt_for_analysis')) {
            try {
                $gpt = $this->runGptAnalysis($text);
                if (is_array($gpt)) {
                    $analysis['gpt_analysis'] = $gpt;
                    // optionally override heuristics with GPT outputs if confidence high
                    if (isset($gpt['fulfillment_score'])) {
                        $analysis['fulfillment_score'] = $gpt['fulfillment_score'];
                    }
                    if (isset($gpt['cs_rating'])) {
                        $analysis['cs_rating'] = $gpt['cs_rating'];
                    }
                    if (isset($gpt['sentiment'])) {
                        $analysis['sentiment'] = $gpt['sentiment'];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('GPT analysis failed: '.$e->getMessage());
            }
        }

        return $analysis;
    }

    protected function estimateSentiment(string $text): string
    {
        $pos = ['thank','thanks','great','okay','ok','confirmed','yes','sure','happy','appreciate'];
        $neg = ['no',' not ','never','hate','angry','cancel','problem','issue','complain','wrong','delay'];

        $score = 0;
        $t = strtolower($text);
        foreach ($pos as $p) if (strpos($t, $p) !== false) $score += 1;
        foreach ($neg as $n) if (strpos($t, $n) !== false) $score -= 1;

        if ($score > 0) return 'positive';
        if ($score < 0) return 'negative';
        return 'neutral';
    }

    protected function extractIntent(string $text): string
    {
        $t = strtolower($text);
        if (preg_match('/(confirm|confirmed|confirmed delivery|i confirm|i will pay|i will send)/', $t)) return 'confirm_order';
        if (preg_match('/(cancel|don\'t want|do not want|not going to)/', $t)) return 'cancel';
        if (preg_match('/(call me back|i will call|later|call me)/', $t)) return 'call_later';
        if (preg_match('/(address|change address|reschedule|pickup)/', $t)) return 'reschedule_or_address_change';
        return 'unknown';
    }

    protected function extractKeywords(string $text): array
    {
        // very simple keyword extraction; replace with better NLP if desired
        $words = array_filter(array_unique(array_map('trim', preg_split('/\W+/', strtolower($text)))), function($w){
            return strlen($w) > 3;
        });
        return array_slice($words, 0, 10);
    }

    protected function estimateFulfillmentProbability(string $text, string $intent, string $sentiment): int
    {
        // base
        $score = 50;

        if ($intent === 'confirm_order') $score += 35;
        if ($intent === 'cancel') $score -= 40;
        if ($intent === 'call_later') $score -= 10;
        if ($intent === 'reschedule_or_address_change') $score -= 5;

        if ($sentiment === 'positive') $score += 10;
        if ($sentiment === 'negative') $score -= 15;

        // keywords like "paid","payment","transfer" => boost
        if (preg_match('/paid|payment|transferred|sent money|mpesa|till|account/i', $text)) $score += 20;

        // clamp 0-100
        return max(0, min(100, (int)$score));
    }

    protected function rateCustomerService(string $text): int
    {
        // 1-5 stars
        // heuristics: count politeness markers from agent + customer
        $score = 3;

        // positives
        if (preg_match_all('/thank|thanks|appreciate|pleased|good service|well done|nice/i', $text, $m)) {
            $score += min(2, floor(count($m[0]) / 2));
        }
        // negatives
        if (preg_match_all('/rude|angry|not helpful|unhelpful|frustrat|complain|complaint/i', $text, $m2)) {
            $score -= min(2, floor(count($m2[0]) / 1));
        }

        if (preg_match('/agent took long|on hold|hold time|waited for/i', $text)) $score -= 1;

        return max(1, min(5, $score));
    }

    protected function runGptAnalysis(string $text): ?array
    {
        $apiKey = config('services.openai.key');
        if (!$apiKey) return null;

        $prompt = "Analyze the following call transcript. Return a JSON object with keys:
- sentiment: positive|neutral|negative
- fulfillment_score: integer 0-100
- cs_rating: integer 1-5
- key_phrases: array of short phrases
- rationale: short text (one or two sentences)

Transcript:
\"\"\"{$text}\"\"\"";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini', // replace with model you have
            'messages' => [
                ['role' => 'system', 'content' => 'You are a terse analyzer that responds only with JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 400,
            'temperature' => 0.0,
        ]);

        if ($response->failed()) {
            Log::warning('GPT analysis failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }

        $body = $response->json();
        $textReply = $body['choices'][0]['message']['content'] ?? null;
        if (!$textReply) return null;

        // attempt to extract JSON from the reply
        $json = json_decode($textReply, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        // fallback: try to find JSON substring
        if (preg_match('/\{.*\}/s', $textReply, $m)) {
            $j = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE) return $j;
        }

        return null;
    }
}
