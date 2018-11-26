<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>WebSockets Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.js"></script>
    <script src="https://js.pusher.com/4.3/pusher.min.js"></script>
</head>

<body>
<div class="container" id="app">
    <div class="card col-xs-12">
        <div class="card-header">
            <form id="connect" class="form-inline" role="form">
                <label class="my-1 mr-2" for="client">Client:</label>
                <select class="form-control form-control-sm mr-2" name="client" id="client" v-model="client">
                    <option v-for="client in clients" :value="client">@{{ client.name }}</option>
                </select>
                <label class="my-1 mr-2" for="client">Port:</label>
                <input class="form-control form-control-sm mr-2" v-model="port" placeholder="Port">
                <button v-if="! connected" type="submit" @click.prevent="connect" class="mr-2 btn btn-sm btn-primary">Connect</button>
                <button v-if="connected" type="submit" @click.prevent="disconnect" class="btn btn-sm btn-danger">Disconnect</button>
            </form>
            <div id="status"></div>
        </div>
        <div class="card-body">
            <div v-if="connected">
                <h4>Event Creator</h4>
                <form>
                    <div class="row">
                        <div class="col">
                            <input type="text" class="form-control" v-model="form.channel" placeholder="Channel">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" v-model="form.event" placeholder="Event">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col">
                            <div class="form-group">
                                <textarea placeholder="Data" v-model="form.data" class="form-control" id="data" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row text-right">
                        <div class="col">
                            <button type="submit" @click.prevent="sendEvent" class="btn btn-sm btn-primary">Send event</button>
                        </div>
                    </div>
                </form>
            </div>
            <h4>Events</h4>
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
                    <tr v-for="log in logs.slice().reverse()">
                        <td><span class="badge" :class="getBadgeClass(log)">@{{ log.type }}</span></td>
                        <td>@{{ log.socketId }}</td>
                        <td>@{{ log.details }}</td>
                        <td>@{{ log.time }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    new Vue({
        el: '#app',

        data: {
            connected: false,
            pusher: null,
            port: 6001,
            client: null,
            clients: {!! json_encode($clients) !!},
            form: {
                channel: null,
                event: null,
                data: null
            },
            logs: [],
        },

        methods: {
            connect() {
                this.pusher = new Pusher(this.client.appKey, {
                    wsHost: window.location.hostname,
                    wsPort: this.port,
                    authEndpoint: '{{ config('websockets.dashboard.path') }}/auth',
                    enabledTransports: ['ws', 'flash']
                });

                this.pusher.connection.bind('state_change', states => {
                    $('div#status').text("Channels current state is " + states.current);
                });

                this.pusher.connection.bind('connected', () => {
                    this.connected = true;
                });

                this.pusher.connection.bind('disconnected', () => {
                        this.connected = false;
                        this.logs = [];
                });

                this.subscribeToChannel('disconnection');

                this.subscribeToChannel('connection');

                this.subscribeToChannel('vacated');

                this.subscribeToChannel('occupied');

                this.subscribeToChannel('subscribed');

                this.subscribeToChannel('client-message');

                this.subscribeToChannel('api-message');
            },

            disconnect() {
                this.pusher.disconnect();
            },

            subscribeToChannel(channel) {
                this.pusher.subscribe('{{ \BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Dashboard::LOG_CHANNEL_PREFIX }}'+channel)
                    .bind('log-message', (data) => {
                    this.logs.push(data);
                });
            },

            getBadgeClass(log) {
                if (log.type === 'occupied' || log.type === 'connection') {
                    return 'badge-primary';
                }
                if (log.type === 'vacated') {
                    return 'badge-warning';
                }
                if (log.type === 'disconnection') {
                    return 'badge-error';
                }
                if (log.type === 'api_message') {
                    return 'badge-info';
                }
                return 'badge-secondary';
            },

            sendEvent() {
                $.post('{{ config('websockets.dashboard.path') }}/event', {
                    key: this.client.appKey,
                    secret: this.client.appSecret,
                    appId: this.client.appId,
                    channel: this.form.channel,
                    event: this.form.event,
                    data: this.form.data,
                }).fail(e => {
                    alert('Error sending event.');
                });
            }
        }
    });
</script>
</body>
</html>