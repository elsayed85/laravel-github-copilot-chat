<?php

namespace Elsayed85\CopilotChat\Core;

class CompletionRequest
{
    public bool $stream;

    public bool $intent;

    public string $model;

    public float $temperature;

    public int $top_p;

    public int $n;

    public function __construct(public array $messages)
    {
        $this->stream = config('github-copilot-chat.stream');
        $this->intent = config('github-copilot-chat.intent');
        $this->model = config('github-copilot-chat.model');
        $this->temperature = config('github-copilot-chat.temperature');
        $this->top_p = config('github-copilot-chat.top_p');
        $this->n = config('github-copilot-chat.n');
    }
}
