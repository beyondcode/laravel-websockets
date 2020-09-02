<?php

namespace BeyondCode\LaravelWebSockets\WebSockets\Channels;

use BeyondCode\LaravelWebSockets\Concerns\PrivatelyChannelable;

class PrivateChannel extends Channel
{
    use PrivatelyChannelable;
}
