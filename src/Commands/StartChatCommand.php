<?php

namespace Elsayed85\CopilotChat\Commands;

use Elsayed85\CopilotChat\CopilotChat;
use Illuminate\Console\Command;
use PhpPkg\CliMarkdown\CliMarkdown;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

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

    protected $converter;

    public function __construct()
    {
        parent::__construct();

        $this->copilot = new CopilotChat();

        $config = [];

        $this->converter = new CliMarkdown();
    }

    public function handle(): void
    {
        $this->info('Welcome to Copilot Chat');

        $this->info("
/***
 *       _____  _  _    _             _        _____               _  _         _
 *      / ____|(_)| |  | |           | |      / ____|             (_)| |       | |
 *     | |  __  _ | |_ | |__   _   _ | |__   | |      ___   _ __   _ | |  ___  | |_
 *     | | |_ || || __|| '_ \ | | | || '_ \  | |     / _ \ | '_ \ | || | / _ \ | __|
 *     | |__| || || |_ | | | || |_| || |_) | | |____| (_) || |_) || || || (_) || |_
 *      \_____||_| \__||_| |_| \__,_||_.__/   \_____|\___/ | .__/ |_||_| \___/  \__|
 *                                                         | |
 *                                                         |_|
 */
");

        if (! $this->copilot->hasGithubToken()) {
            $this->error('Your Need to Authenticate with Github First');
            $check = $this->call('github:auth');

            if ($check == 0) {
                $this->error('You need to auth with Github first');

                return;
            }

            $codeConfirmed = $this->confirmCodeAuth();

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
        $this->handelChating();
        while (true) {
            $this->handelChating();
        }

    }

    private function handelChating(): void
    {
        $message = $this->askForInput();
        if ($this->handelExit($message)) {
            return;
        }
        $response = $this->sendMessage($message);
        $this->getRender($response);
    }

    /**
     * @throws \Exception
     */
    private function sendMessage($message)
    {
        $response = $this->copilot->addMessage($message)->send();

        $text = $response['text'];

        if ($response['type'] == 'error') {
            warning($text);

            return false;
        }

        return $text;
    }

    private function confirmCodeAuth(): bool
    {
        try {
            return confirm(
                label: 'Do you entered the code successfully?',
                default: true,
            );
        } catch (\Exception) {
            return $this->confirm('Do you entered the code successfully?', true);
        }
    }

    private function askForInput(): string
    {
        try {
            return text(
                label: 'Type Your Message',
                placeholder: 'message ...',
                required: true
            );
        } catch (\Exception) {
            return $this->ask('Type Your Message');
        }
    }

    private function handelExit($message): bool
    {
        if ($message == 'exit') {
            $this->info('Bye Bye');

            return true;
        }

        return false;
    }

    public function getRender($response): void
    {
        $text = $this->converter->render($response);
        $this->line('Copilot: '.$text);
    }
}
