<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\Concerns\PresencelyChannelable;

class PresenceChannel extends Channel
{
    use PresencelyChannelable;
}
