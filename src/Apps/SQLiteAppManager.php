<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class SQLiteAppManager implements AppManager
{
    /**
     * The database connection.
     *
     * @var DatabaseInterface
     */
    protected $database;

    /**
     * Initialize the class.
     *
     * @param  DatabaseInterface  $database
     */
    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Get all apps.
     *
     * @return PromiseInterface
     */
    public function all(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('SELECT * FROM `apps`')
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($result->rows);
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }

    /**
     * Get app by id.
     *
     * @param  string|int  $appId
     * @return PromiseInterface
     */
    public function findById($appId): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('SELECT * from apps WHERE `id` = :id', ['id' => $appId])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->rows[0]));
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }

    /**
     * Get app by app key.
     *
     * @param  string  $appKey
     * @return PromiseInterface
     */
    public function findByKey($appKey): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('SELECT * from apps WHERE `key` = :key', ['key' => $appKey])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->rows[0]));
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }

    /**
     * Get app by secret.
     *
     * @param  string  $appSecret
     * @return PromiseInterface
     */
    public function findBySecret($appSecret): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('SELECT * from apps WHERE `secret` = :secret', ['secret' => $appSecret])
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->rows[0]));
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }

    /**
     * Map the app into an App instance.
     *
     * @param  array|null  $app
     * @return \BeyondCode\LaravelWebSockets\Apps\App|null
     */
    protected function convertIntoApp(?array $appAttributes): ?App
    {
        if (! $appAttributes) {
            return null;
        }

        $app = new App(
            $appAttributes['id'],
            $appAttributes['key'],
            $appAttributes['secret']
        );

        if (isset($appAttributes['name'])) {
            $app->setName($appAttributes['name']);
        }

        if (isset($appAttributes['host'])) {
            $app->setHost($appAttributes['host']);
        }

        if (isset($appAttributes['path'])) {
            $app->setPath($appAttributes['path']);
        }

        $app
            ->enableClientMessages((bool) $appAttributes['enable_client_messages'])
            ->enableStatistics((bool) $appAttributes['enable_statistics'])
            ->setCapacity($appAttributes['capacity'] ?? null)
            ->setAllowedOrigins(array_filter(explode(',', $appAttributes['allowed_origins'])));

        return $app;
    }

    /**
     * @inheritDoc
     */
    public function createApp($appData): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('
            INSERT INTO apps (id, key, secret, name, host, path, enable_client_messages, enable_statistics, capacity, allowed_origins)
            VALUES (:id, :key, :secret, :name, :host, :path, :enable_client_messages, :enable_statistics, :capacity, :allowed_origins)
        ', $appData)
            ->then(function (Result $result) use ($deferred) {
                $deferred->resolve();
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }
}
