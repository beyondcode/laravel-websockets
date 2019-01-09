<?php

namespace BeyondCode\LaravelWebSockets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatabaseApp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'host',
        'key',
        'secret',
        'enable_client_messages',
        'enable_statistics'
    ];

    protected $guarded = [
        'key',
        'secret'
    ];

    public function getTable()
    {
        return config('websockets.database.tables.apps');
    }
}
