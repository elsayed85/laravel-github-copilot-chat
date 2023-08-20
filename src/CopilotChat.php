<?php

namespace Elsayed85\CopilotChat;

use Carbon\Carbon;
use Elsayed85\CopilotChat\Core\CompletionRequest;
use Elsayed85\CopilotChat\Core\Message;
use Illuminate\Support\Facades\Http;

class CopilotChat
{
    private ?string $github_token;

    private ?string $token;

    private ?int $tokenExpiresAt;

    private array $messages = [];

    public function __construct()
    {
        $this->github_token = cache()->get('github_token');
        $this->tokenExpiresAt = cache()->get('token_expires_at');
    }

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

    public function generateToken()
    {
        return Http::withHeaders([
            'User-Agent' => config('github-copilot-chat.user_agent'),
        ])
            ->withToken($this->getGithubToken())
            ->get('https://api.github.com/copilot_internal/v2/token')
            ->json();
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

            cache()->put('copilot_token', $this->token, $this->tokenExpiresAt);
            cache()->put('token_expires_at', $this->tokenExpiresAt, $this->tokenExpiresAt);
        } else {
            $this->token = cache()->get('copilot_token');
        }
    }

    public function query($req): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => config('github-copilot-chat.user_agent'),
        ])
            ->asJson()
            ->withToken($this->token)
            ->post('https://copilot-proxy.githubusercontent.com/v1/chat/completions', $req);

        $dataset = explode("\n", $response->body());

        $final_string = '';

        foreach ($dataset as $str) {
            $line = substr($str, 6);
            if (str_contains($str, 'data: ')) {

                $responseData = json_decode($line, true);

                $choices = $responseData['choices'] ?? null;

                if ($choices) {
                    foreach ($choices as $choice) {
                        $delta = $choice['delta'] ?? null;

                        if ($delta) {
                            $final_string .= $delta['content'] ?? '';
                        }
                    }
                }
            }
        }

        return $final_string;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage($message, $role = 'user'): static
    {
        $this->messages[] = new Message($message, $role);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function addMessages(array $messages): static
    {
        foreach ($messages as $message) {
            if (! isset($message['content']) || ! isset($message['role'])) {
                throw new \Exception('Each message must have a content and role');
            }
        }

        $messages = array_map(function ($message) {
            return new Message($message['content'], $message['role']);
        }, $messages);

        $this->messages = array_merge($this->messages, $messages);

        return $this;
    }

    public function clearMessages(): void
    {
        $this->messages = [];
    }

    /**
     * @throws \Exception
     */
    public function send(): ?string
    {
        if (empty($this->messages)) {
            throw new \Exception('No messages to send');
        }

        $req = new CompletionRequest($this->messages);

        $response = $this->query($req);

        if ($response) {
            $this->addMessage($response, 'assistant');

            return $response;
        }

        throw new \Exception('No response from Copilot');
    }

    public function getGithubToken(): ?string
    {
        return cache()->get('github_token');
    }
}
