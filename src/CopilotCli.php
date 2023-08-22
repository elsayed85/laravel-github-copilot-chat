<?php

namespace Elsayed85\CopilotChat;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CopilotCli
{
    private const API_URL = 'https://copilot-proxy.githubusercontent.com/v1/engines/copilot-labs-codex/completions';

    const MAX_TOKENS_STRUCTURE_COMMAND = 1024;

    const MAX_TOKENS_SUGGEST_COMMAND = 256;

    private int $maxTokens; // max number of tokens to generate

    private int $n; // number of tokens to generate

    private bool $stream; // whether to stream back partial messages as they are available

    private float $temperature; // controls randomness. 0 = deterministic. 1 = maximum randomness.

    private float $top_p; // controls diversity via nucleus sampling. 1 = no diversity.

    private array $stop; // tokens at which to stop generating further tokens

    private ?string $github_token;

    private ?string $token;

    private ?int $tokenExpiresAt;

    private array $prompts;

    private string $question;

    private string $answer;

    public function __construct()
    {
        $this->github_token = Cache::get('github_token');
        $this->tokenExpiresAt = Cache::get('token_expires_at');
        $this->n = config('github-copilot-chat.cli.n');
        $this->stream = config('github-copilot-chat.cli.stream');
        $this->temperature = config('github-copilot-chat.cli.temperature');
        $this->top_p = config('github-copilot-chat.cli.top_p');
        $this->stop = config('github-copilot-chat.cli.stop');
        $this->prompts = config('github-copilot-chat.cli.prompts');
    }

    /**
     * @throws \Exception
     */
    public function init(): static
    {
        $this->getOrRefreshToken();

        return $this;
    }

    public function getExpiresIn(): string
    {
        return Carbon::createFromTimestamp($this->tokenExpiresAt)->diff(Carbon::now())->format('%H:%I:%S');
    }

    public function hasGithubToken(): bool
    {
        return is_string($this->github_token) && ! empty($this->github_token);
    }

    public function tokenExpired(): bool
    {
        return $this->tokenExpiresAt && $this->tokenExpiresAt < Carbon::now()->timestamp;
    }

    public function getGithubToken(): ?string
    {
        return Cache::get('github_token');
    }

    /**
     * @throws \Exception
     */
    public function generateToken()
    {
        $response = Http::withHeaders([
            'User-Agent' => config('github-copilot-chat.user_agent'),
        ])
            ->withToken($this->getGithubToken())
            ->get('https://api.github.com/copilot_internal/v2/token');

        if ($response->status() != 200) {
            throw new \Exception('Could not generate token');
        }

        return $response->json();
    }

    public function getOrRefreshToken(): void
    {
        if ($this->tokenExpiresAt < Carbon::now()->timestamp) {
            $response = $this->generateToken();

            if (! isset($response['token']) || ! isset($response['expires_at'])) {
                throw new \Exception('Could not generate token');
            }

            $this->token = $response['token'];
            $this->tokenExpiresAt = $response['expires_at'];

            Cache::put('copilot_token', $this->token, $this->tokenExpiresAt);
            Cache::put('token_expires_at', $this->tokenExpiresAt, $this->tokenExpiresAt);
        } else {
            $this->token = Cache::get('copilot_token');
        }
    }

    /**
     * @throws \Exception
     */
    private function query($key): static
    {
        $response = Http::withHeaders([
            'User-Agent' => config('github-copilot-chat.user_agent'),
        ])
            ->asJson()
            ->withToken($this->token)
            ->post(self::API_URL, [
                'prompt' => $this->getPrompt($key),
                'max_tokens' => $this->maxTokens,
                'n' => $this->n,
                'stream' => $this->stream,
                'temperature' => $this->temperature,
                'top_p' => $this->top_p,
                'stop' => $this->stop,
            ]);

        if ($response->status() != 200) {
            throw new \Exception('Could not generate Answer');
        }

        $data = $response->body();

        $this->answer = $this->getFinalString($key, $data);

        return $this;
    }

    private function suggest($key): string
    {
        return $this->setMaxTokens(self::MAX_TOKENS_SUGGEST_COMMAND)->addAnotherStop('\nR:\n')->query($key)->getAnswer();
    }

    public function explanation(): string
    {
        return $this->setMaxTokens(self::MAX_TOKENS_STRUCTURE_COMMAND)->query('explanations')->getAnswer();
    }

    private function getPrompt($key): string
    {
        $stub = $this->prompts[$key];
        $content = file_get_contents(__DIR__.'/../stubs/'.$stub);
        $question = $key == 'explanations' ? $this->answer : $this->question;

        return str_replace('__USER__QUESTION__', $question, $content);
    }

    public function shell(): string
    {
        return $this->suggest('shell');
    }

    public function git(): string
    {
        return $this->suggest('git');
    }

    public function gitCli(): string
    {
        return $this->suggest('gh');
    }

    private function getFinalString($key, string $data): string
    {
        $dataset = explode("\n", $data);

        // remove empty lines
        $dataset = array_filter($dataset, fn ($line) => ! empty($line));

        $final_string = '';
        $finish_reason = null;
        foreach ($dataset as $str) {
            $line = substr($str, 6);
            if (str_contains($str, 'data: ')) {
                $responseData = json_decode($line, true);

                $choices = $responseData['choices'] ?? null;

                if ($choices) {
                    foreach ($choices as $choice) {
                        $text = $choice['text'] ?? null;
                        $finish_reason = $choice['finish_reason'] ?? null;
                        if ($finish_reason == 'stop') {
                            break;
                        }
                        $final_string .= $text;
                    }
                }
            }

            if ($finish_reason == 'stop') {
                break;
            }
        }

        if ($key != 'explanations') {
            $final_string = explode("\n", $final_string)[0];
            $final_string = trim($final_string);
        }

        return $final_string;
    }

    private function setMaxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    private function addAnotherStop($stop): static
    {
        $this->stop[] = $stop;

        return $this;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }
}
