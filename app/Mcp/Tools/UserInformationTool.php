<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Lists the seeded dummy users in ID order, optionally filtered by name or email, by the first letters of the name, and limited to a number of results. Call it with no arguments to list every dummy user.')]
class UserInformationTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'starts_with' => ['sometimes', 'nullable', 'string', 'max:20'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $search = trim((string) $request->get('search'));
        $startsWith = trim((string) $request->get('starts_with'));
        $limit = (int) ($request->get('limit') ?? 50);

        $users = User::query()
            ->where('email', 'like', 'dummy.user.%@example.test')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"),
            ))
            ->when($startsWith !== '', fn ($query) => $query->where('name', 'like', "{$startsWith}%"))
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'email']);

        $answer = $users->isEmpty()
            ? 'No matching dummy user found.'
            : 'Found '.$users->count().' matching dummy user'.($users->count() === 1 ? '' : 's').'.';

        return Response::json([
            'answer' => $answer,
            'count' => $users->count(),
            'users' => $users,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description('Part of a name or email address to look for, e.g. "Amina" or "user.007". Leave empty to skip this filter.'),

            'starts_with' => $schema->string()
                ->description('Only return users whose name begins with these letters, e.g. "A" or "Ang". Leave empty to skip this filter.'),

            'limit' => $schema->integer()
                ->description('The maximum number of users to return, between 1 and 50. Users are returned in ID order, so a limit of 5 returns the first five.')
                ->default(50),
        ];
    }

    /**
     * Get the tool's output schema.
     *
     * @return array<string, Type>
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'answer' => $schema->string()->description('A short summary of the search result.')->required(),
            'count' => $schema->integer()->description('The number of matching dummy users returned.')->required(),
            'users' => $schema->array()->description('Matching dummy user IDs, names, and email addresses.')->required(),
        ];
    }
}
