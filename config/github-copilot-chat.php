<?php

return [
    'stream' => true,
    'intent' => false,
    'model' => 'copilot-chat',
    'temperature' => 0.1,
    'top_p' => 1,
    'n' => 1,

    'client_id' => '01ab8ac9400c4e429b23', // Don't change this
    'user_agent' => 'GithubCopilot/3.99.99', // Don't change this

    'cli' => [
        'n' => 1,
        'stream' => true,
        'temperature' => 0,
        'top_p' => 1,
        'stop' => [
            '---',
        ],

        'prompts' => [
            'shell' => 'shell.stub',
            'git' => 'git.stub',
            'gh' => 'gh.stub',
            'explanations' => 'explanations.stub',
        ],
    ],
];
