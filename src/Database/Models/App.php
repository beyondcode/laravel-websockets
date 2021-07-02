<?php

namespace BeyondCode\LaravelWebSockets\Database\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
        'active',
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

        static::creating(function (App $app) {
            $app->key = Str::random(40);
            $app->secret = Str::random(40);
        });
    }
}
