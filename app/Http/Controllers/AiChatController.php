<?php

namespace App\Http\Controllers;

use App\Ai\Agents\AssistantAgent;
use App\Http\Requests\AiChatRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Responses\Data\ToolCall;
use Throwable;

class AiChatController extends Controller
{
    /**
     * Send the user's message to the AI agent and return its reply.
     */
    public function __invoke(AiChatRequest $request): JsonResponse
    {
        try {
            $response = (new AssistantAgent($request->history()))
                ->prompt($request->validated('message'));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The AI provider could not answer right now. Please try again in a moment.',
            ], 502);
        }

        return response()->json([
            'reply' => $response->text,
            'tools' => $response->toolCalls
                ->map(fn (ToolCall $call): array => [
                    'name' => $call->name,
                    'arguments' => $call->arguments,
                ])
                ->values(),
        ]);
    }
}
