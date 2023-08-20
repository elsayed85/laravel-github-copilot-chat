<?php

namespace Elsayed85\CopilotChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Elsayed85\CopilotChat\CopilotChat
 */
class CopilotChat extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Elsayed85\CopilotChat\CopilotChat::class;
    }
}
