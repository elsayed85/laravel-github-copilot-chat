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

    private array $rules;

    public function __construct()
    {
        $this->github_token = cache()->get('github_token');
        $this->tokenExpiresAt = cache()->get('token_expires_at');

        $this->rules = [
            "You are an AI assistant. it's okay if user asks for non-technical questions, you can answer them.",
            'When asked for your name, you must respond with "GitHub Copilot".',
            "Follow the user's requirements carefully & to the letter.",
            'If the user asks for code or technical questions, you must provide code suggestions and adhere to technical information.',
            'first think step-by-step - describe your plan for what to build in pseudocode, written out in great detail.',
            'Then output the code in a single code block.',
            'Minimize any other prose.',
            'Keep your answers short and impersonal.',
            'Use Markdown formatting in your answers.',
            'Make sure to include the programming language name at the start of the Markdown code blocks.',
            'Avoid wrapping the whole response in triple backticks.',
            'You should always generate 4 short suggestions for the next user turns that are relevant to the conversation and not offensive',
        ];
    }

    /**
     * @throws \Exception
     */
    public function init(): static
    {
        $this->getOrRefreshToken();

        $this->addMessage($this->getRulesString())->send();

        return $this;
    }

    private function getRulesString()
    {
        $rules = '';

        foreach ($this->rules as $rule) {
            $rules .= $rule."\n";
        }

        return $rules;
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

            cache()->put('copilot_token', $this->token, $this->tokenExpiresAt);
            cache()->put('token_expires_at', $this->tokenExpiresAt, $this->tokenExpiresAt);
        } else {
            $this->token = cache()->get('copilot_token');
        }
    }

    /**
     * @throws \Exception
     */
    public function query($req): array
    {
        $response = Http::withHeaders([
            'User-Agent' => config('github-copilot-chat.user_agent'),
        ])
            ->asJson()
            ->withToken($this->token)
            ->post('https://copilot-proxy.githubusercontent.com/v1/chat/completions', $req);

        if ($response->status() != 200) {

            dd($response->json());
            $error = $response->json()['error'];
            $code = strtoupper($error['code']);
            $message = $error['message'];

            return [
                'type' => 'error',
                'text' => "Error: $code - $message",
            ];
        }

        $data = $response->body();

        return [
            'type' => 'success',
            'text' => $this->getFinalString($data),
        ];
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

    public function clearMessages(): void
    {
        $this->messages = [];
    }

    /**
     * @throws \Exception
     */
    public function send(): array
    {
        if (empty($this->messages)) {
            throw new \Exception('No messages to send');
        }

        $req = new CompletionRequest($this->messages);

        $response = $this->query($req);

        if ($response) {
            $this->addMessage($response['text'], 'assistant');

            return $response;
        }

        throw new \Exception('No response from Copilot');
    }

    public function getGithubToken(): ?string
    {
        return cache()->get('github_token');
    }

    public function getFinalString(string $data): string
    {
        $dataset = explode("\n", $data);
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
}
