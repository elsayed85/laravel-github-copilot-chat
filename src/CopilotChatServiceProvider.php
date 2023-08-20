<?php

namespace Elsayed85\CopilotChat;

use Elsayed85\CopilotChat\Commands\AuthGithubCommand;
use Elsayed85\CopilotChat\Commands\GenerateAccessTokenCommand;
use Elsayed85\CopilotChat\Commands\GenerateCopilotTokenCommand;
use Elsayed85\CopilotChat\Commands\StartChatCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CopilotChatServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-github-copilot-chat')
            ->hasConfigFile()
            ->hasCommands([
                AuthGithubCommand::class,
                GenerateAccessTokenCommand::class,
                GenerateCopilotTokenCommand::class,
                StartChatCommand::class,
            ]);
    }
}
