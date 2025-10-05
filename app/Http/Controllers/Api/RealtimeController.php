<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class RealtimeController extends Controller
{
    //



    //  public function createSession(Request $request)
    // {
    //     $context = $request->input('context', []);

    //     $response = Http::withToken(env('OPENAI_API_KEY'))
    //         ->post('https://api.openai.com/v1/realtime/sessions', [
    //             'model' => 'gpt-realtime',
    //             'voice' => 'marin',
    //             'instructions' => "You are a polite AI voice assistant for a courier company.
    //                 The caller's phone number is {$context['phoneNumber']}.
    //                 Their latest order details: " . json_encode($context['order']),
    //         ]);

    //     return $response->json();
    // }



    public function createSession(Request $request)
    {
        $context = $request->input('context', []);

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/realtime/sessions', [
                'model' => 'gpt-realtime',
                'voice' => 'marin',
                'instructions' => "You are Boxleo AI Assistant. Greet the caller, confirm their order details, 
            and help them track or update their delivery status. Speak naturally and keep responses short.
            The caller's phone number is {$context['phoneNumber']}.
            Recent order details: " . json_encode($context['order']),
            ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to create realtime session'], 500);
        }

        return $response->json();
    }
}
