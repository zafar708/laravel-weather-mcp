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
        ->assertSee('Name or email');
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

    UserServer::tool(UserInformationTool::class, ['search' => 'Amina Khan'])
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

    UserServer::tool(UserInformationTool::class, ['starts_with' => 'a'])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('Angus Veum')
        ->assertSee('Amina Khan')
        ->assertDontSee('Bilal Ahmed');
});

it('finds customers by first name', function () {
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

    UserServer::tool(UserInformationTool::class, ['search' => 'Angus'])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('Angus Veum')
        ->assertSee('Angus McKenzie')
        ->assertDontSee('Amina Khan');
});

it('returns only the first users in id order when a limit is given', function () {
    User::factory()->count(3)->sequence(
        ['name' => 'Amina Khan', 'email' => 'dummy.user.001@example.test'],
        ['name' => 'Bilal Ahmed', 'email' => 'dummy.user.002@example.test'],
        ['name' => 'Chand Bibi', 'email' => 'dummy.user.003@example.test'],
    )->create();

    UserServer::tool(UserInformationTool::class, ['limit' => 2])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('Amina Khan')
        ->assertSee('Bilal Ahmed')
        ->assertDontSee('Chand Bibi');
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
            'arguments' => ['search' => 'Amina'],
        ],
    ])
        ->assertOk()
        ->assertSee('Amina Khan')
        ->assertSee('dummy.user.001@example.test');
});

it('returns all dummy users when no filters are given', function () {
    User::factory()->count(2)->sequence(
        ['name' => 'Amina Khan', 'email' => 'dummy.user.001@example.test'],
        ['name' => 'Bilal Ahmed', 'email' => 'dummy.user.002@example.test'],
    )->create();

    UserServer::tool(UserInformationTool::class, [])
        ->assertOk()
        ->assertSee('Found 2 matching dummy users.')
        ->assertSee('dummy.user.001@example.test')
        ->assertSee('dummy.user.002@example.test');
});

it('rejects a limit outside the allowed range', function () {
    UserServer::tool(UserInformationTool::class, ['limit' => 0])
        ->assertHasErrors();

    UserServer::tool(UserInformationTool::class, ['limit' => 51])
        ->assertHasErrors();
});
