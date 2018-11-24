<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>WebSockets Console</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://js.pusher.com/4.3/pusher.min.js"></script>
</head>

<body>
<div class="container">
    <div class="panel panel-default">
        <div id="status"></div>
        <div class="panel-heading">
            <span class="panel-title">WebSockets Console</span>
            <form id="connect" class="form-inline" role="form">
                <div class="form-group">
                    <select class="form-control" name="app" id="app">
                        @foreach ($clients as $client)
                            <option value="{{ $client->appKey }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Connect</button>
            </form>
            <form id="disconnect" class="form-inline" role="form">
                <button type="submit" class="btn btn-danger">Disconnect</button>
            </form>
        </div>
        <div class="panel-body col-md-8">
            <h3>Events <span class="badge" id="counter"></span></h3>
            <table id="events" class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Type</th>
                    <th>Socket</th>
                    <th>Details</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(function(){
        $('#connect').submit(function(event) {
            connect($('#app').val());
            event.preventDefault();
        });

        $('#disconnect').submit(function(event) {
            var appKey = $('#appKey').val("");
            var secret = $('#secret').val("");
            event.preventDefault();
        });
    });

    function connect(appKey) {
        pusher = new Pusher(appKey, {
            wsHost: window.location.hostname,
            wsPort: 6001,
            authEndpoint: '{{ config('websockets.dashboard.path') }}/auth',
            enabledTransports: ['ws', 'flash']
        });

        pusher.subscribe('private-logger-new_connection')
            .bind('private-logger-connection', function(data) {
            alert(JSON.stringify(data));
        });
    }
</script>
</body>
</html>