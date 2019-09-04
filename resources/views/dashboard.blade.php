<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title>WebSockets Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous"></script>
    <script src="https://js.pusher.com/4.3/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>

<body>
<div class="container" id="app">
    <div class="card col-xs-12 mt-4">
        <div class="card-header">
            <form id="connect" class="form-inline" role="form">
                <label class="my-1 mr-2" for="app">App:</label>
                <select class="form-control form-control-sm mr-2" name="app" id="app" v-model="app">
                    <option v-for="app in apps" :value="app">@{{ app.name }}</option>
                </select>
                <label class="my-1 mr-2" for="app">Port:</label>
                <input class="form-control form-control-sm mr-2" v-model="port" placeholder="Port">
                <button v-if="! connected" type="submit" @click.prevent="connect" class="mr-2 btn btn-sm btn-primary">
                    Connect
                </button>
                <button v-if="connected" type="submit" @click.prevent="disconnect" class="btn btn-sm btn-danger">
                    Disconnect
                </button>
            </form>
            <div id="status"></div>
        </div>
        <div class="card-body">
            <div v-if="connected && app.statisticsEnabled">
                <h4>Realtime Statistics</h4>
                <div id="statisticsChart" style="width: 100%; height: 250px;"></div>
            </div>
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
                                <textarea placeholder="Data" v-model="form.data" class="form-control" id="data"
                                          rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row text-right">
                        <div class="col">
                            <button type="submit" @click.prevent="sendEvent" class="btn btn-sm btn-primary">Send event
                            </button>
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
            chart: null,
            pusher: null,
            app: null,
            port: {{ $port }},
            apps: {!! json_encode($apps) !!},
            form: {
                channel: null,
                event: null,
                data: null
            },
            logs: [],
        },

        mounted() {
            this.app = this.apps[0] || null;
        },

        methods: {
            connect() {
                this.pusher = new Pusher(this.app.key, {
                    wsHost: this.app.host === null ? window.location.hostname : this.app.host,
                    wsPort: this.port === null ? 6001 : this.port,
                    wssPort: this.port === null ? 6001 : this.port,
                    wsPath: this.app.path === null ? '' : this.app.path,
                    disableStats: true,
                    authEndpoint: '/{{ request()->path() }}/auth',
                    auth: {
                        headers: {
                            'X-CSRF-Token': "{{ csrf_token() }}",
                            'X-App-ID': this.app.id
                        }
                    },
                    enabledTransports: ['ws', 'flash']
                });

                this.pusher.connection.bind('state_change', states => {
                    $('div#status').text("Channels current state is " + states.current);
                });

                this.pusher.connection.bind('connected', () => {
                    this.connected = true;

                    this.loadChart();
                });

                this.pusher.connection.bind('disconnected', () => {
                    this.connected = false;
                    this.logs = [];
                });

                this.pusher.connection.bind('error', event => {
                    if (event.error.data.code === 4100) {
                        $('div#status').text("Maximum connection limit exceeded!");
                        this.connected = false;
                        this.logs = [];
                        throw new Error("Over capacity");
                    }
                });

                this.subscribeToAllChannels();

                this.subscribeToStatistics();
            },

            disconnect() {
                this.pusher.disconnect();
            },

            loadChart() {
                $.getJSON('/{{ request()->path() }}/api/'+this.app.id+'/statistics', (data) => {

                    let chartData = [
                        {
                            x: data.peak_connections.x,
                            y: data.peak_connections.y,
                            type: 'lines',
                            name: '# Peak Connections'
                        },
                        {
                            x: data.websocket_message_count.x,
                            y: data.websocket_message_count.y,
                            type: 'bar',
                            name: '# Websocket Messages'
                        },
                        {
                            x: data.api_message_count.x,
                            y: data.api_message_count.y,
                            type: 'bar',
                            name: '# API Messages'
                        }
                    ];
                    let layout = {
                        margin: {
                            l: 50,
                            r: 0,
                            b: 50,
                            t: 50,
                            pad: 4
                        }
                    };

                    this.chart = Plotly.newPlot('statisticsChart', chartData, layout);
                });
            },

            subscribeToAllChannels() {
                [
                    'disconnection',
                    'connection',
                    'vacated',
                    'occupied',
                    'subscribed',
                    'client-message',
                    'api-message',
                ].forEach(channelName => this.subscribeToChannel(channelName))
            },

            subscribeToChannel(channel) {
                this.pusher.subscribe('{{ \BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger::LOG_CHANNEL_PREFIX }}' + channel)
                    .bind('log-message', (data) => {
                        this.logs.push(data);
                    });
            },

            subscribeToStatistics() {
                this.pusher.subscribe('{{ \BeyondCode\LaravelWebSockets\Dashboard\DashboardLogger::LOG_CHANNEL_PREFIX }}statistics')
                    .bind('statistics-updated', (data) => {
                            var update = {
                                x:  [[data.time], [data.time], [data.time]],
                                y: [[data.peak_connection_count], [data.websocket_message_count], [data.api_message_count]]
                            };

                            Plotly.extendTraces('statisticsChart', update, [0, 1, 2]);
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
                $.post('/{{ request()->path() }}/event', {
                    _token: '{{ csrf_token() }}',
                    key: this.app.key,
                    secret: this.app.secret,
                    appId: this.app.id,
                    channel: this.form.channel,
                    event: this.form.event,
                    data: this.form.data,
                }).fail(() => {
                    alert('Error sending event.');
                });
            }
        }
    });
</script>
</body>
</html>
