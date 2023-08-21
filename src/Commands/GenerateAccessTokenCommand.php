<?php

namespace Elsayed85\CopilotChat\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GenerateAccessTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:generate-access-token';

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
        $device_code = Cache::get('device_code');

        if (! $device_code) {
            $this->error('You need to run php artisan github:auth first and go to https://github.com/login/device/ and enter the code:');

            return 0;
        }

        $response = Http::asForm()->post(
            'https://github.com/login/oauth/access_token',
            [
                'client_id' => config('github-copilot-chat.client_id'),
                'scope' => 'user:email',
                'device_code' => $device_code,
                'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            ]
        );

        $response = explode('&', $response->body());

        $access_token = explode('=', $response[0])[1];

        Cache::forever('github_token', $access_token);
        Cache::forget('user_code');
        Cache::forget('device_code');

        $this->info('Your Github Token is: '.$access_token);

        return 1;
    }
}
