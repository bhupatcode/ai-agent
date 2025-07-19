<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat');
    }

    public function send(Request $request)
    {
        try {
            $userInput = $request->input('message');
            $imageText = '';

            // Handle image if uploaded
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Step 1: Convert image to base64 for OCR API
                $imageData = base64_encode(file_get_contents($image->getRealPath()));

                // Step 2: Send image to OCR.Space for text extraction
                $ocrResponse = Http::asForm()->post('https://api.ocr.space/parse/image', [
                    'base64Image' => 'data:image/png;base64,' . $imageData,
                    'language' => 'eng',
                    'apikey' => env('OCR_SPACE_API_KEY'),
                ]);

                $ocrResult = $ocrResponse->json();
                $imageText = $ocrResult['ParsedResults'][0]['ParsedText'] ?? '';
            }

            // Combine user input with OCR text if available
            $finalMessage = trim($userInput . "\n\n" . $imageText);

            // Step 3: Send combined message to OpenAI
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                    ['role' => 'user', 'content' => $finalMessage],
                ],
            ]);

            return response()->json([
                'reply' => $response['choices'][0]['message']['content'] ?? 'No reply from AI.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'reply' => '⚠️ Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function chatWithTogetherAI(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('TOGETHER_AI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.together.xyz/v1/chat/completions', [
                'model' => 'mistralai/Mixtral-8x7B-Instruct-v0.1',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $request->input('message'),
                    ]
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'reply' => $data['choices'][0]['message']['content'] ?? 'No reply received.',
                ]);
            } else {
                return response()->json(['error' => 'API Error', 'details' => $response->body()], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server Exception',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
