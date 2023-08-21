<?php

namespace Elsayed85\CopilotChat\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AuthGithubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:auth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Authenticate with Github';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $response = Http::asForm()->post(
            'https://github.com/login/device/code',
            [
                'client_id' => config('github-copilot-chat.client_id'),
                'scope' => 'user:email',
            ]
        )->body();

        $response = explode('&', $response);
        $user_code = explode('=', $response[3])[1];
        $device_code = explode('=', $response[0])[1];

        Cache::put('user_code', $user_code, 60 * 60 * 24);
        Cache::put('device_code', $device_code, 60 * 60 * 24);

        $this->info('Got To https://github.com/login/device/ and enter the code: '.$user_code);

        return 1;
    }
}
