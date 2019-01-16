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

    <div class="card col-xs-12 mt-4">
        <div class="card-header text-right">
            <a class="btn btn-success" href="{{ route('websockets.admin.create') }}">Create app</a>
        </div>
        <div class="card-body">
            <h4>Apps</h4>
            <table id="events" class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Host</th>
                    <th>Client messages</th>
                    <th>Statistics</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($apps as $app)
                    <tr>
                        <td>
                            {{ $app->name }} @if (!$app->active) <span class="badge badge-danger">DISABLED</span> @endif<br />
                            <p class="small mt-2 mb-1"><b>Key: </b> {{ $app->key }}</p>
                            <p class="small my-0"><b>Secret: </b> {{ $app->secret }}</p>
                        </td>
                        <td>{{ $app->host }}</td>
                        <td>{{ $app->enable_client_messages ? 'YES' : "NO" }}</td>
                        <td>{{ $app->enable_statistics ? 'YES' : "NO" }}</td>
                        <td>
                            <a class="btn btn-block btn-primary" href="{{ route('websockets.admin.edit', ['app' => $app->id]) }}">Edit</a>
                            <form method="post" action="{{ route('websockets.admin.destroy', ['app' => $app->id]) }}" onsubmit="return(confirm('Are you sure?'))">
                                @csrf
                                <button type="submit" class="btn btn-block mt-1 btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
