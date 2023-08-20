<?php

namespace Elsayed85\CopilotChat\Commands;

use Elsayed85\CopilotChat\CopilotChat;
use Illuminate\Console\Command;
use PhpPkg\CliMarkdown\CliMarkdown;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class StartChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copilot:chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'copilot chat';

    protected $copilot;

    public function __construct()
    {
        parent::__construct();

        $this->copilot = new CopilotChat();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Welcome to Copilot Chat');

        if (! $this->copilot->hasGithubToken()) {
            $this->error('Your Need to Authenticate with Github First');
            $check = $this->call('github:auth');

            if ($check == 0) {
                $this->error('You need to auth with Github first');

                return;
            }

            $codeConfirmed = confirm(
                label: 'Do you entered the code successfully?',
                default: true,
            );

            if (! $codeConfirmed) {
                $this->error('You need to auth with Github first');

                return;
            }

            $check = $this->call('github:generate-access-token');

            if ($check == 0) {
                $this->error('You need to auth with Github first');

                return;
            }

            $this->info('You are authenticated with Github');

        }

        $this->copilot->init();

        $message = text(
            label: 'Type Your Message',
            placeholder: 'Hi My Name is ',
            required: true
        );

        $response = $this->sendMessage($message);

        $this->info('You: '.$message);
        $this->info('Copilot: '.$response);

        while (true) {
            $message = text(
                label: 'Type Your Message',
                placeholder: 'Hi My Name is ',
                required: true
            );
            $response = $this->sendMessage($message);
            $this->info('You: '.$message);
            $this->info('Copilot: '.$response);
        }

    }

    private function sendMessage($message)
    {
        try {
            $response = $this->copilot->addMessage($message)->send();
            // convert markdown to html
            return (new CliMarkdown())->render($response);
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return false;
        }
    }
}
