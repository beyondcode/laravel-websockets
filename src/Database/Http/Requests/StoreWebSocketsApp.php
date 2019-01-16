<?php

namespace BeyondCode\LaravelWebSockets\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebSocketsApp extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'host' => 'nullable',
            'enable_client_messages' => 'nullable|boolean',
            'enable_statistics' => 'nullable|boolean',
        ];
    }
}
