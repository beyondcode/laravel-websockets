<?php

namespace BeyondCode\LaravelWebSockets\Apps;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

class MysqlAppManager implements AppManager
{
    /**
     * The database connection.
     *
     * @var ConnectionInterface
     */
    protected $database;

    /**
     * Initialize the class.
     *
     * @param  ConnectionInterface  $database
     */
    public function __construct(ConnectionInterface $database)
    {
        $this->database = $database;
    }

    protected function getTableName(): string
    {
        return config('websockets.managers.mysql.table');
    }

    /**
     * Get all apps.
     *
     * @return PromiseInterface
     */
    public function all(): PromiseInterface
    {
        $deferred = new Deferred();

        $this->database->query('SELECT * FROM `'.$this->getTableName().'`')
            ->then(function (QueryResult $result) use ($deferred) {
                $deferred->resolve($result->resultRows);
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

        $this->database->query('SELECT * from `'.$this->getTableName().'` WHERE `id` = ?', [$appId])
            ->then(function (QueryResult $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->resultRows[0]));
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

        $this->database->query('SELECT * from `'.$this->getTableName().'` WHERE `key` = ?', [$appKey])
            ->then(function (QueryResult $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->resultRows[0]));
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

        $this->database->query('SELECT * from `'.$this->getTableName().'` WHERE `secret` = ?', [$appSecret])
            ->then(function (QueryResult $result) use ($deferred) {
                $deferred->resolve($this->convertIntoApp($result->resultRows[0]));
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

        $this->database->query(
            'INSERT INTO `'.$this->getTableName().'` (`id`, `key`, `secret`, `name`, `enable_client_messages`, `enable_statistics`, `allowed_origins`, `capacity`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$appData['id'], $appData['key'], $appData['secret'], $appData['name'], $appData['enable_client_messages'], $appData['enable_statistics'], $appData['allowed_origins'] ?? '', $appData['capacity'] ?? null])
            ->then(function () use ($deferred) {
                $deferred->resolve();
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }
}
