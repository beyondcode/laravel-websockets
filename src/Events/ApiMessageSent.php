<?php

namespace BeyondCode\LaravelWebSockets\Events;

use Ratchet\ConnectionInterface;
use stdClass;

class ApiMessageSent
{
    public $appId;
    public $channeldId;
    public $name;
    public $data;

    public function __construct($appId, $channeldId, $name, $data)
    {
        $this->appId = $appId;

        $this->channeldId = $channeldId;

        $this->name = $name;

        $this->data = $data;
    }
}