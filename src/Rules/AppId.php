<?php

namespace BeyondCode\LaravelWebSockets\Rules;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use Illuminate\Contracts\Validation\Rule;

class AppId implements Rule
{
    /**
     * Websocket App Manager;
     *
     * @var \BeyondCode\LaravelWebSockets\Contracts\AppManager
     */
    protected $appManager;

    /**
     * AppId constructor.
     *
     * @param  \BeyondCode\LaravelWebSockets\Contracts\AppManager  $appManager
     */
    public function __construct(AppManager $appManager)
    {
        $this->appManager = $appManager;
    }

    /**
     * Create a new rule.
     *
     * @param  mixed  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return (bool)$this->appManager->findById($value);
    }

    /**
     * The validation message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'There is no app registered with the given id. Make sure the websockets config file contains an app for this id or that your custom AppManager returns an app for this id.';
    }
}
