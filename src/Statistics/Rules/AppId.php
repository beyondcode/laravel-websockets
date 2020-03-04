<?php

namespace BeyondCode\LaravelWebSockets\Statistics\Rules;

use BeyondCode\LaravelWebSockets\Apps\AppProvider;
use Illuminate\Contracts\Validation\Rule;

class AppId implements Rule
{
    public function passes($attribute, $value)
    {
        $appProvider = app(AppProvider::class);

        return $appProvider->findById($value) ? true : false;
    }

    public function message()
    {
        return 'There is no app registered with the given id. Make sure the websockets config file contains an app for this id or that your custom AppProvider returns an app for this id.';
    }
}
