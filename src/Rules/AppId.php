<?php

namespace BeyondCode\LaravelWebSockets\Rules;

use BeyondCode\LaravelWebSockets\Contracts\AppManager;
use Illuminate\Contracts\Validation\Rule;

class AppId implements Rule
{
    /**
     * Create a new rule.
     *
     * @param  mixed  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $manager = app(AppManager::class);

        return $manager->findById($value) ? true : false;
    }

    /**
     * The validation message.
     *
     * @return string
     */
    public function message()
    {
        return 'There is no app registered with the given id. Make sure the websockets config file contains an app for this id or that your custom AppManager returns an app for this id.';
    }
}
