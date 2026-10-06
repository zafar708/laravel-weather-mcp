<?php

use App\Mcp\Servers\UserServer;
use App\Mcp\Tools\UserInformationTool;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('renders the users chat page', function () {
    $this->get(route('users-chat'))
        ->assertOk()
        ->assertSee('User Data Assistant')
        ->assertSee('Ask a question about the sample users');
});

it('returns matching dummy users without exposing other accounts', function () {
    User::factory()->create([
        'name' => 'Amina Khan',
        'email' => 'dummy.user.001@example.test',
    ]);
    User::factory()->create([
        'name' => 'Private Account',
        'email' => 'private@example.test',
    ]);

    $response = UserServer::tool(UserInformationTool::class, [
        'question' => 'What is Amina Khan email?',
    ]);

    $response
        ->assertOk()
        ->assertSee('Found 1 matching dummy user.')
        ->assertSee('dummy.user.001@example.test')
        ->assertDontSee('Private Account')
        ->assertDontSee('private@example.test');
});

it('finds dummy users whose names start with the requested letter', function () {
    User::factory()->create([
        'name' => 'Angus Veum',
        'email' => 'dummy.user.001@example.test',
    ]);
    User::factory()->create([
        'name' => 'Amina Khan',
        'email' => 'dummy.user.002@example.test',
    ]);
    User::factory()->create([
        'name' => 'Bilal Ahmed',
        'email' => 'dummy.user.003@example.test',
    ]);

    UserServer::tool(UserInformationTool::class, [
        'question' => 'is there any customer name starting with a exist',
    ])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('Angus Veum')
        ->assertSee('Amina Khan')
        ->assertDontSee('Bilal Ahmed');
});

it('finds a customer by first name when the prompt includes customer details', function () {
    User::factory()->create([
        'name' => 'Angus Veum',
        'email' => 'dummy.user.001@example.test',
    ]);
    User::factory()->create([
        'name' => 'Angus McKenzie',
        'email' => 'dummy.user.002@example.test',
    ]);
    User::factory()->create([
        'name' => 'Amina Khan',
        'email' => 'dummy.user.003@example.test',
    ]);

    UserServer::tool(UserInformationTool::class, [
        'question' => 'find the Angus customer details',
    ])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('Angus Veum')
        ->assertSee('Angus McKenzie')
        ->assertDontSee('Amina Khan');
});

it('returns dummy user information through the MCP HTTP endpoint', function () {
    User::factory()->create([
        'name' => 'Amina Khan',
        'email' => 'dummy.user.001@example.test',
    ]);

    $this->postJson('/mcp/users', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'user-information-tool',
            'arguments' => ['question' => 'Amina Khan ka email batao'],
        ],
    ])
        ->assertOk()
        ->assertSee('Amina Khan')
        ->assertSee('dummy.user.001@example.test');
});

it('returns all seeded dummy users for a general question', function () {
    User::factory()->count(2)->sequence(
        ['name' => 'Amina Khan', 'email' => 'dummy.user.001@example.test'],
        ['name' => 'Bilal Ahmed', 'email' => 'dummy.user.002@example.test'],
    )->create();

    UserServer::tool(UserInformationTool::class, [
        'question' => 'Show all users',
    ])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('dummy.user.001@example.test')
        ->assertSee('dummy.user.002@example.test');
});

it('rejects a missing question', function () {
    UserServer::tool(UserInformationTool::class, [])
        ->assertHasErrors();
});
