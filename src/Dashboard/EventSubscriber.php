<?php

namespace BeyondCode\LaravelWebSockets\Dashboard;

use BeyondCode\LaravelWebSockets\Events\ChannelVacated;
use BeyondCode\LaravelWebSockets\Events\ClientMessageSent;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Dashboard;
use Illuminate\Events\Dispatcher;

class EventSubscriber
{
    public function onChannelVacated(ChannelVacated $event)
    {
        Dashboard::vacated($event->connection, $event->channelId);
    }

    public function onClientMessageSent(ClientMessageSent $event)
    {
        Dashboard::clientMessage($event->connection, $event->payload);
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(ChannelVacated::class, static::class . '@onChannelVacated');
        $events->listen(ClientMessageSent::class, static::class . '@onClientMessageSent');
    }
}