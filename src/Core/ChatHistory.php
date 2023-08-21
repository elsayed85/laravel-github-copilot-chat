<?php

namespace Elsayed85\CopilotChat\Core;

use Illuminate\Support\Facades\Cache;

class ChatHistory
{
    private $histories = [];

    public function __construct()
    {
        $this->histories = Cache::get('histories', []);
    }

    public function getHistories(): mixed
    {
        return $this->histories;
    }

    public function addHistory(string $history): void
    {
        //
    }

    public function clearHistories(): void
    {
        $this->histories = [];

        Cache::put('histories', $this->histories, 60 * 60 * 24);
    }
}
