<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIController extends Controller
{
    private function saveChatMessage(int $userId, string $content, string $type): void
    {
        ChatMessage::create([
            'user_id' => $userId,
            'content' => $content,
            'type' => $type,
        ]);
    }

    public function askForHelp(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string',
            ]);

            $query = $request->input('query');

            $user = auth()->user();

            $this->saveChatMessage($user->id, $query, 'user');

            $chatHistory = ChatMessage::where('user_id', $user->id)
                ->orderBy('created_at')
                ->get(['type', 'content']);

            $formattedMessages = $chatHistory->map(function ($message) {
                return [
                    'role' => $message->type === 'user' ? 'user' : 'assistant',
                    'content' => $message->content,
                ];
            })->toArray();

            $formattedMessages[] = [
                'role' => 'user',
                'content' => $query,
            ];

//            return response()->json(['response' => $formattedMessages]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => $formattedMessages,
                'temperature' => 0,
                'max_tokens' => 2048,
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Появи се проблем при AI услугата.'], 500);

            }

            $responseData = $response->json();
            $chatBotMessage = $responseData['choices'][0]['message']['content'] ?? 'Няма отговор от AI.';

            $this->saveChatMessage($user->id, $chatBotMessage, 'bot');

            return response()->json(['response' => $chatBotMessage]);
        }catch (Exception $e) {
            // Log the exception message and stack trace
            Log::error('Error in AIController@askForHelp', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Възникна грешка при обработката на заявката.'], 500);
        }

    }
}
