<?php

namespace Elsayed85\CopilotChat\Core;

class Message
{
    public function __construct(public string $content, public string $role)
    {
        //
    }
}
