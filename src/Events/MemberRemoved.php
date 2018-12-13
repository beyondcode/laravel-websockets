<?php

namespace BeyondCode\LaravelWebSockets\Events;

class MemberRemoved
{
    public $channel;
    public $data;

    public function __construct($channel, $data)
    {
        $this->channel = $channel;
        $this->data    = $data;
    }
}
