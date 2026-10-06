<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\UserInformationTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('User Server')]
#[Version('1.0.0')]
#[Instructions('Answers questions about the 50 seeded dummy users. Only dummy users are available; real account records are never returned.')]
class UserServer extends Server
{
    protected array $tools = [
        UserInformationTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
