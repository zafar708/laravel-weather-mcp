<?php

namespace App\Ai\Agents;

use App\Mcp\Tools\CurrentWeatherTool;
use App\Mcp\Tools\RainForecastTool;
use App\Mcp\Tools\UserInformationTool;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(['gemini-lite', 'gemini'])]
#[MaxSteps(5)]
#[Timeout(10)]
class AssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function __construct(protected array $history = []) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are a friendly assistant for a Laravel demo application.

        You can look up live weather, rain forecasts, and the application's sample users by calling your tools.
        Always call a tool when the question needs weather or user data. Never invent temperatures, forecasts, names, or email addresses.
        If a tool returns an error or no results, say so plainly.
        If a question is unrelated to your tools, answer briefly from general knowledge.
        Reply in the same language and script the user writes in (for example Roman Urdu or English), and keep answers short.
        INSTRUCTIONS;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $message): Message => new Message($message['role'], $message['content']),
            $this->history,
        );
    }

    /**
     * Get the tools available to the agent.
     *
     * The application's existing MCP tools are passed in directly and run in-process.
     *
     * @return list<object>
     */
    public function tools(): iterable
    {
        return [
            app(CurrentWeatherTool::class),
            app(RainForecastTool::class),
            app(UserInformationTool::class),
        ];
    }
}
