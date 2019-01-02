<?php

namespace BeyondCode\LaravelWebSockets\HttpApi\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use BeyondCode\LaravelWebSockets\HttpApi\Controllers\Concerns\BroadcastsEvents;

class TriggerBatchedEventsController extends Controller
{
    use BroadcastsEvents;

    public function __invoke(Request $request)
    {
        $this->ensureValidSignature($request);

        $validator = validator($request->json()->all(), [
            'batch.*.name' => 'required',
            'batch.*.data' => 'required',
            'batch.*.channel' => 'required',
        ], [
            'required' => 'Missing required parameter: :attribute',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'body' => 'Failed input validation.',
                'status' => 400,
                'validation_errors' => $validator->errors()->toArray(),
            ], 400);
        }

        foreach ($request->json()->get('batch', []) as $event) {
            $this->broadcastEventForAppToChannel(
                $request->appId,
                $event['channel'],
                $event['name'],
                $event['data'],
                $event['socket_id'] ?? null
            );
        }

        return $request->json()->all();
    }
}
