<?php

namespace BeyondCode\LaravelWebSockets\Server\Logger;

use Ratchet\ConnectionInterface;

class ConnectionLogger extends Logger implements ConnectionInterface
{
    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    public static function decorate(ConnectionInterface $app): self
    {
        $logger = app(self::class);

        return $logger->setConnection($app);
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    protected function getConnection()
    {
        return $this->connection;
    }

    public function send($data)
    {

         $socketId = $this->connection->socketId ?? null;

        $this->info("Connection id {$socketId} sending message {$data}");


        $m = json_decode($data);

        if ($m->channel ==  'videoStream') {
            $message = json_decode($m->data);

            if($message->message->data->type== 'video_stream'){
                $sdp = $message->message->data->sdp;
                $streams = session()->get('streams');
                $streams[$message->message->userId] = $sdp;
                session(['streams'=>$streams ]);
            }

            if($message->message->data->type== 'getVideoStream'){
                $streams = session()->get('streams');
                $newData = [];
                $newData['channel'] = $m->channel;
                $newData['event'] = $m->event;
                $newData['data']['message'] = ['streams'=>$streams];

                $data = json_encode($newData);

            }

        }
        $this->connection->send($data);

    }

    public function close()
    {
        $this->warn("Connection id {$this->connection->socketId} closing.");

        $this->connection->close();
    }

    public function __set($name, $value)
    {
        return $this->connection->$name = $value;
    }

    public function __get($name)
    {
        return $this->connection->$name;
    }

    public function __isset($name)
    {
        return isset($this->connection->$name);
    }

    public function __unset($name)
    {
        unset($this->connection->$name);
    }
}
