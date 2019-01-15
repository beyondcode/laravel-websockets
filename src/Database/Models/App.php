<?php

namespace BeyondCode\LaravelWebSockets\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class App extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'host',
        'key',
        'secret',
        'enable_client_messages',
        'enable_statistics',
    ];

    protected $guarded = [
        'key',
        'secret',
    ];

    protected $casts = [
        'enable_client_messages' => 'bool',
        'enable_statistics' => 'bool',
    ];

    public function getTable()
    {
        return config('websockets.database.tables.apps');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function(App $app) {
            $app->key = str_random(40);
            $app->secret = str_random(40);
        });
    }
}
