<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>WebSockets Admin Panel</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>
</head>

<body>
<div class="container" id="app">
    @if (session('success'))
        <div class="alert alert-success my-5">
            {{ session('success') }}
        </div>
    @endif

    @if (session('errors'))
        <div class="alert alert-danger my-5">
            {{ session('errors') }}
        </div>
    @endif

    <div class="card col-xs-12 mt-4">
        <div class="card-header">
            <a class="btn btn-danger left" href="{{ route('websockets.admin.index') }}">Back</a>
        </div>
        <div class="card-body">
            @if ($app->id)
                <h4 class="mb-4">Edit app</h4>

                <form method="post" action="{{ route('websockets.admin.update', $app->id) }}">
            @else
                <h4 class="mb-4">Create app</h4>

                <form method="post" action="{{ route('websockets.admin.store') }}">
            @endif
                @csrf

                <div class="form-check mt-2 mb-4">
                    <input class="form-check-input" type="checkbox" value="1" name="active" id="active" @if(optional($app)->active) checked @endif />
                    <label class="form-check-label" for="active">
                        Active
                    </label>
                </div>

                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ optional($app)->name ?? old('name') }}" />
                </div>

                <div class="form-group">
                    <label>Host</label>
                    <input type="text" name="host" class="form-control" value="{{ optional($app)->host ?? old('host') }}" />
                </div>

                <div class="form-check mt-2 mb-4">
                    <input class="form-check-input" type="checkbox" value="1" name="enable_client_messages" id="enable_client_messages" @if(optional($app)->enable_client_messages) checked @endif />
                    <label class="form-check-label" for="enable_client_messages">
                        Enable client messages
                    </label>
                </div>

                <div class="form-check mt-2 mb-4">
                    <input class="form-check-input" type="checkbox" value="1" name="enable_statistics" id="enable_statistics" @if(optional($app)->enable_statistics) checked @endif />
                    <label class="form-check-label" for="enable_statistics">
                        Enable statistics
                    </label>
                </div>

                <div class="form-group">
                    <label>Key</label>
                    <input type="text" readonly class="form-control-plaintext text-muted" value="{{ $app->key ?? "Unavailable" }}">
                </div>

                <div class="form-group">
                    <label>Secret</label>
                    <input type="text" readonly class="form-control-plaintext text-muted" value="{{ $app->secret ?? "Unavailable" }}">
                </div>

                <button type="submit" class="btn btn-success btn-block">Save</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
