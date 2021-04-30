<?php

namespace BeyondCode\LaravelWebSockets\Server\Loggers;

use Ratchet\ConnectionInterface;

class ConnectionLogger extends Logger implements ConnectionInterface
{
    /**
     * The connection to watch.
     *
     * @var \Ratchet\ConnectionInterface
     */
    protected $connection;

    /**
     * Create a new instance and add a connection to watch.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return self
     */
    public static function decorate(ConnectionInterface $app): self
    {
        $logger = app(self::class);

        return $logger->setConnection($app);
    }

    /**
     * Set a new connection to watch.
     *
     * @param  \Ratchet\ConnectionInterface  $connection
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Send data through the connection.
     *
     * @param  string  $data
     * @return void
     */
    public function send($data)
    {
        $socketId = $this->connection->socketId ?? null;

        $this->info("Connection id {$socketId} sending message {$data}");

        $this->connection->send($data);
    }

    /**
     * Close the connection.
     *
     * @return void
     */
    public function close()
    {
        $this->warn("Connection id {$this->connection->socketId} closing.");

        $this->connection->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __set($name, $value)
    {
        return $this->connection->$name = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->connection->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset($name)
    {
        return isset($this->connection->$name);
    }

    /**
     * {@inheritdoc}
     */
    public function __unset($name)
    {
        unset($this->connection->$name);
    }
}
