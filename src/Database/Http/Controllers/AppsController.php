<?php

namespace BeyondCode\LaravelWebSockets\Database\Http\Controllers;

use Illuminate\Http\Request;
use BeyondCode\LaravelWebSockets\Database\Models\App;
use BeyondCode\LaravelWebSockets\Database\Http\Requests\StoreWebSocketsApp;

class AppsController
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $apps = App::when($request->has('q'), function ($query) use ($request) {
            $query->whereLike('name', $request->get('q'));
        })->get();

        return view('websockets::apps.index', [
            'apps' => $apps,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('websockets::apps.edit', [
            'app' => new App(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreWebSocketsApp  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWebSocketsApp $request)
    {
        $app = new App();

        $app->name = $request->name;
        $app->host = $request->host;
        $app->enable_client_messages = $request->get('enable_client_messages', false);
        $app->enable_statistics = $request->get('enable_statistics', false);
        $app->save();

        return redirect(route('websockets.admin.edit', ['app' => $app->id]))->with('success', 'Record created.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  App  $app
     * @return \Illuminate\Http\Response
     */
    public function edit(App $app)
    {
        return view('websockets::apps.edit', [
            'app' => $app,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  StoreWebSocketsApp  $request
     * @param  App  $app
     * @return \Illuminate\Http\Response
     */
    public function update(StoreWebSocketsApp $request, App $app)
    {
        $app->name = $request->name;
        $app->host = $request->host;
        $app->enable_client_messages = $request->get('enable_client_messages', false);
        $app->enable_statistics = $request->get('enable_statistics', false);
        $app->save();

        return redirect(route('websockets.admin.edit', ['app' => $app->id]))->with('success', 'Record saved.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  App  $app
     * @return \Illuminate\Http\Response
     */
    public function destroy(App $app)
    {
        $app->delete();

        return redirect(route('websockets.admin.index'))->with('success', 'Record deleted.');
    }
}
