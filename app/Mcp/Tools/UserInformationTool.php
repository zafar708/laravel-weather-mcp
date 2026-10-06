<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Searches the seeded dummy users and returns matching names and email addresses.')]
class UserInformationTool extends Tool
{
    private const IGNORED_QUERY_WORDS = [
        'a', 'about', 'all', 'an', 'and', 'any', 'bata', 'batao', 'beginning',
        'begins', 'customer', 'customers', 'details', 'do', 'does', 'email',
        'exist', 'exists', 'find', 'for', 'get', 'give', 'hai', 'hain', 'has',
        'i', 'info', 'information', 'is', 'ka', 'ke', 'ki', 'kro', 'kry',
        'list', 'me', 'mera', 'mere', 'mujhe', 'mujhy', 'name', 'of', 'please',
        'return', 'show', 'start', 'starting', 'starts', 'table', 'tamam', 'the',
        'there', 'user', 'users', 'what', 'which', 'who', 'with',
    ];

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $question = $request->string('question')->toString();
        $nameStartsWith = null;

        if (preg_match('/\b(?:start(?:ing|s)?|begin(?:ning|s)?)\s+with\s+([\p{L}\p{N}])/iu', $question, $prefixMatch) === 1) {
            $nameStartsWith = Str::lower($prefixMatch[1]);
            $question = Str::replace($prefixMatch[0], ' ', $question);
        }

        $terms = collect(preg_split(
            '/[^\p{L}\p{N}@._+-]+/u',
            Str::lower($question),
            -1,
            PREG_SPLIT_NO_EMPTY,
        ))
            ->reject(fn (string $term): bool => in_array($term, self::IGNORED_QUERY_WORDS, true))
            ->values();

        $users = User::query()
            ->where('email', 'like', 'dummy.user.%@example.test')
            ->when($nameStartsWith !== null, function (Builder $query) use ($nameStartsWith): void {
                $query->where('name', 'like', $nameStartsWith.'%');
            })
            ->when($terms->isNotEmpty(), function (Builder $query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->where(function (Builder $query) use ($term): void {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
                }
            })
            ->orderBy('id')
            ->limit(50)
            ->get(['id', 'name', 'email']);

        $answer = $users->isEmpty()
            ? 'No user found.'
            : 'Found '.$users->count().' matching user'.($users->count() === 1 ? '' : 's').'.';

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
            'question' => $schema->string()
                ->description('A question or search request about the seeded dummy users, such as "Show all users" or "Find Amina Khan".')
                ->required(),
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
            'count' => $schema->integer()->description('The number of matching dummy users.')->required(),
            'users' => $schema->array()->description('Matching dummy user IDs, names, and email addresses.')->required(),
        ];
    }
}
