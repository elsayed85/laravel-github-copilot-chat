<?php

namespace Elsayed85\CopilotChat\Commands;

use Elsayed85\CopilotChat\Facades\CopilotChat;
use Illuminate\Console\Command;

class GenerateCopilotTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copilot:generate-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Copilot Token';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $token = CopilotChat::getOrRefreshToken();

        $this->info('Your Copilot Token is: '.$token);

        // it will expire in
        $this->info('It will expire in: '.CopilotChat::getExpiresIn());
    }
}
