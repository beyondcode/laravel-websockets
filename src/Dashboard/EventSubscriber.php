<?php

namespace BeyondCode\LaravelWebSockets\Dashboard;

use BeyondCode\LaravelWebSockets\Events\ApiMessageSent;
use BeyondCode\LaravelWebSockets\Events\ChannelOccupied;
use BeyondCode\LaravelWebSockets\Events\ChannelVacated;
use BeyondCode\LaravelWebSockets\Events\ClientMessageSent;
use BeyondCode\LaravelWebSockets\Events\ConnectionEstablished;
use BeyondCode\LaravelWebSockets\Events\SubscribedToChannel;
use Illuminate\Events\Dispatcher;

class EventSubscriber
{
    public function onApiMessageSent(ApiMessageSent $event)
    {
        DashboardLogger::apiMessage(
            $event->appId,
            $event->channeldId,
            $event->name,
            $event->data
        );
    }

    public function onChannelOccupied(ChannelOccupied $event)
    {
        DashboardLogger::occupied($event->connection, $event->channelId);
    }

    public function onChannelVacated(ChannelVacated $event)
    {
        DashboardLogger::vacated($event->connection, $event->channelId);
    }

    public function onClientMessageSent(ClientMessageSent $event)
    {
        DashboardLogger::clientMessage($event->connection, $event->payload);
    }

    public function onConnectionEstablished(ConnectionEstablished $event)
    {
        DashboardLogger::connection($event->connection);
    }

    public function onSubscribedToChannel(SubscribedToChannel $event)
    {
        DashboardLogger::subscribed($event->connection, $event->channelId);
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiMessageSent::class, static::class. '@onApiMessageSent');
        $events->listen(ChannelOccupied::class, static::class . '@onChannelOccupied');
        $events->listen(ChannelVacated::class, static::class . '@onChannelVacated');
        $events->listen(ClientMessageSent::class, static::class . '@onClientMessageSent');
        $events->listen(ConnectionEstablished::class, static::class . '@onConnectionEstablished');
        $events->listen(SubscribedToChannel::class, static::class . '@onSubscribedToChannel');
    }
}