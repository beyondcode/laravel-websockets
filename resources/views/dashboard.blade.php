<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

    <title>WebSockets Dashboard</title>

    <link rel="preload" href="/css/icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    @vite(['resources/js/beekman/beekman.js'])

    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        window.baseURL = '{{ url(request()->path()) }}';
        axios.defaults.baseURL = baseURL;
    </script>

    <style>
        .green-700 {
            background-color: #15803d;
        }
        .red-700 {
            background-color: #b91c1c;
        }
        .gray-700 {
            background-color: #374151;
        }
        .orange-700 {
            background-color: #c2410c;
        }
    </style>
</head>

<body class="font-sans antialiased">
<div id="app" :style="style" :class="{'dark': theme === 'dark'}">
    <div class="flex flex-col h-screen bg-blue-50">
        <header>
            <nav class="bg-theme shadow-md">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <a href="/">
                                    <img src="//admin.beekman.nl/images/beekman-logo-nl.png" alt="Beekman B.V." class="block h-9 w-auto">
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <div v-if="connected" class="flex items-center">
                                <div class="mr-2 text-white">
                                    <input type="checkbox" v-model="autoRefresh"/>
                                    Refresh automatically
                                </div>
                                <button @click.prevent="disconnect" class="bg-red-500 hover:bg-red-600 rounded-full px-3 py-2 text-white focus:outline-none">
                                    Disconnect
                                </button>
                            </div>

                            <button
                                v-else
                                @click.prevent="connect"
                                class="rounded-full px-3 py-2 text-white focus:outline-none"
                                :class="{
              'bg-green-500 hover:bg-green-600': ! connecting,
              'bg-gray-500 cursor-not-allowed': connecting,
            }"
                            >
                                <template v-if="connecting">
                                    Connecting...
                                </template>
                                <template v-else>
                                    Connect
                                </template>
                            </button>

                            <div class="ml-2 cursor-pointer">
                                <svg v-if="theme === 'dark'" @click="theme = 'light'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <svg v-if="theme === 'light'" @click="theme = 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
        <main class="p-6 flex flex-col space-y-2 bg-blue-50" v-if="connected" >

            <div class="flex space-x-2">
                <div class="rounded bg-white w-1/2">
                    <div class="flex justify-between items-center my-6 px-6">
                        <div class="font-semibold uppercase flex justify-between cursor-pointer py-6 dark:text-white" @click.prevent="showLiveStatistics = !showLiveStatistics">
                            <span>Live statistics</span>
                        </div>

                        <div class="space-x-3 flex items-center">
                            <button v-if="!autoRefresh" @click="loadChart" class="rounded bg-theme text-white px-3 py-1 mr-2">
                                Refresh
                            </button>

                            <div class="cursor-pointer dark:text-white" @click.prevent="showLiveStatistics = !showLiveStatistics">
                                <svg v-if="!showLiveStatistics" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <svg v-if="showLiveStatistics" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        id="statisticsChart"
                        class="rounded-b"
                        v-if="showLiveStatistics"
                        style="width: 100%; height: 300px;"
                    ></div>
                </div>
                <div class="rounded bg-white w-1/2">
                    <div class="flex justify-between items-center my-6 px-6">
                        <div class="font-semibold uppercase flex justify-between cursor-pointer py-6 dark:text-white" @click.prevent="activeChannels = !activeChannels">
                            <span>Active channels</span>
                        </div>

                        <div class="space-x-3 flex items-center">
                            <button v-if="!autoRefresh" @click="loadChannels" class="rounded bg-theme text-white px-3 py-1 mr-2">
                                Refresh
                            </button>
                            <div class="cursor-pointer dark:text-white" @click.prevent="activeChannels = !activeChannels">
                                <svg v-if="!activeChannels" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                                <svg v-if="activeChannels" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div v-if="activeChannels" class="w-full bg-white overflow-x-auto overflow-y-auto rounded-b"
                         style="width: 100%; height: 300px;">
                        <table class="w-full divide-y divide-gray-200 rounded-b">
                            <thead>
                            <tr>
                                <th class="px-2 py-1 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider w-11/12">
                                    Channel
                                </th>
                                <th class="px-2 py-1 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider text-right w-1/12">
                                    Connections
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" :class="{'text-white': theme === 'dark'}">
                            <tr v-for="(channel, index) in channels" :key="index">
                                <td class="px-2 py-1 whitespace-no-wrap border-b border-gray-200 w-11/12 text-xs">
                                    @{{ channel.channel }}
                                </td>
                                <td class="px-2 py-1 whitespace-no-wrap border-b border-gray-200 text-right w-1/12 text-xs">
                                    @{{ channel.connections }}
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex flex-col my-6 rounded bg-white">
                <div class="font-semibold uppercase dark:text-white mb-6 flex justify-between py-6 px-6">
                    <span>Server activity</span>
                    <span>@{{logItemsDuringSession}} messages during session</span>
                    <div class="flex">
                        <span v-if="!pauseLogItems" class="cursor-pointer text-orange-500" @click.prevent="pauseLogItems = true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span v-if="pauseLogItems" class="cursor-pointer text-green-500" @click.prevent="pauseLogItems = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        <span class="cursor-pointer text-theme" @click.prevent="clear">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="inline-block w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 rounded-b overflow-x-auto">
                    <table class="w-full table-auto overflow-scroll divide-y divide-gray-200 rounded-b">
                        <thead>
                        <tr>
                            <th class="px-2 py-1 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-2 py-1 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                                Details
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" :class="{'text-white': theme === 'dark'}">
                        <tr v-for="(log, index) in filteredLogs" :key="index">
                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-xs" style="width: 350px; vertical-align:top">
                                <div class="flex justify-between items-center">
                                    <div class="text-left w-1/3">@{{ log.time }}</div>
                                    <div :class="[getBadgeClass(log)]" class="w-2/3 rounded px-3 py-1 inline-block text-sm dark:text-white">
                                        @{{ log.type }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-xs">
                                <pre class="text-xs">@{{ jsonDecode(log.details) }}</pre>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    </main>
</div>
<script>
    new Vue({
        el: '#app',
        data: {
            theme: 'dark',
            logItemsDuringSession: 0,
            pauseLogItems: false,
            maxLogItems: 50,
            activeChannels: true,
            showLiveStatistics: true,
            connected: false,
            connecting: false,
            autoRefresh: true,
            refreshInterval: 3,
            refreshTicker: null,
            chart: null,
            pusher: null,
            app: 'Gis',
            apps: @json($apps),
            logs: [],
            channels: [],
        },
        mounted() {
            this.app = this.apps[0] || null;
        },
        destroyed() {
            if (this.refreshTicker) {
                this.stopRefreshInterval();
            }
        },
        watch: {
            connected(newVal) {
                newVal ? this.startRefreshInterval() : this.stopRefreshInterval();
            },
            autoRefresh(newVal) {
                newVal ? this.startRefreshInterval() : this.stopRefreshInterval();
            },
        },
        computed: {
            filteredLogs() {
                return this.logs.slice().reverse();
            },
            style() {
                return {
                    '--color-theme': '#003a75',
                };
            },
        },
        methods: {
            jsonDecode(data) {
                if (data.payload) {
                    if (typeof data.payload === 'string') {
                        let payload = data.payload.replaceAll('\"', '"');
                        data.payload = JSON.parse(payload);
                    }
                }
              return data;
            },
            clear() {
              this.logs = [];
              this.logItemsDuringSession = 0;
            },
            connect() {
                this.connecting = true;

                this.pusher = new Pusher(this.app.key, {
                    wsHost: this.app.host === null ? window.location.hostname : this.app.host,
                    wsPort: {{ $port }},
                    wssPort: {{ $port }},
                    wsPath: this.app.path === null ? '' : this.app.path,
                    disableStats: true,
                    authEndpoint: `${window.baseURL}/auth`,
                    auth: {
                        headers: {
                            'X-CSRF-Token': "{{ csrf_token() }}",
                            'X-App-ID': this.app.id,
                        },
                    },
                    enabledTransports: ['ws', 'wss'],
                    forceTLS: false,
                });

                this.pusher.connection.bind('state_change', states => {
                    this.connecting = false;
                });

                this.pusher.connection.bind('connected', () => {
                    this.connected = true;
                    this.connecting = false;

                    this.loadChart();
                    this.loadChannels();
                });

                this.pusher.connection.bind('disconnected', () => {
                    this.connected = false;
                    this.connecting = false;
                    this.logs = [];
                    this.chart = null;
                    this.channels = null;
                });

                this.pusher.connection.bind('error', event => {
                    if (event.data.code === 4100) {
                        this.connected = false;
                        this.logs = [];
                        this.chart = null;

                        throw new Error("Over capacity");
                    }

                    this.connecting = false;
                });

                this.subscribeToAllChannels();
            },

            disconnect() {
                this.pusher.disconnect();
                this.connecting = false;
                this.chart = null;
            },

            loadChannels() {
                axios.get(`/api/${this.app.id}/channels`)
                    .then(response => {
                        this.channels  = response.data;
                    });
            },

            loadChart() {
                axios.get(`/api/${this.app.id}/statistics`)
                    .then(response => {
                        let data = response.data;

                        let chartData = [
                            {
                                x: data.peak_connections.x,
                                y: data.peak_connections.y,
                                type: 'lines',
                                name: 'Peak Connections'
                            },
                            {
                                x: data.websocket_messages_count.x,
                                y: data.websocket_messages_count.y,
                                type: 'bar',
                                name: 'Websocket Messages'
                            },
                            {
                                x: data.api_messages_count.x,
                                y: data.api_messages_count.y,
                                type: 'bar',
                                name: 'API Messages'
                            },
                        ];

                        let layout = {
                            plot_bgcolor: "rgba(0,0,0,0)",
                            paper_bgcolor: "rgba(0,0,0,0)",
                            uirevision: 'true',
                            autosize: true,
                            showlegend: true,
                            legend: {
                                orientation: 'h'
                            },
                            margin: {
                                l: 40,
                                r: 40,
                                b: 10,
                                t: 15,
                                pad: 4,
                            },
                            modebar: {
                                orientation: 'v',
                            },
                        };

                        let config = {
                            scrollZoom: true,
                            modeBarButtonsToRemove: ['autoscale', 'lasso2d', 'select2d', 'zoomIn2d', 'zoom2d', 'zoomOut2d', 'toggleSpikelines', 'hoverClosestCartesian']
                        };

                        this.chart = this.chart
                            ? Plotly.react('statisticsChart', chartData, layout, config)
                            : Plotly.newPlot('statisticsChart', chartData, layout, config);

                    });
            },

            subscribeToAllChannels() {
                @json($channels).
                forEach(channelName => this.subscribeToChannel(channelName))
            },

            subscribeToChannel(channel) {
                this.pusher.subscribe(`{{ $logPrefix }}${channel}`)
                    .bind('log-message', (data) => {
                        if (!this.pauseLogItems) {

                            this.logItemsDuringSession++;

                            this.logs.push(data);
                            if (this.logs.length > this.maxLogItems) {
                                this.logs.splice(0, 1);
                            }
                        }
                    });
            },

            getBadgeClass(log) {
                if (['connected', 'connection', 'subscribed'].includes(log.type)) {
                    return 'bg-green-500 text-white';
                }

                if (['replicator-subscribed'].includes(log.type)) {
                    return 'green-700 text-white';
                }

                if (['unsubscribed'].includes(log.type)) {
                    return 'orange-700 text-white';
                }

                if (['disconnected', 'disconnection', 'replicator-unsubscribed'].includes(log.type)) {
                    return 'red-700 text-white';
                }

                if (['api-message', 'replicator-message-received'].includes(log.type)) {
                    return 'bg-theme text-white';
                }

                return 'gray-700 text-white';
            },

            startRefreshInterval() {
                this.refreshTicker = setInterval(function () {
                    this.loadChart();
                    this.loadChannels();
                }.bind(this), this.refreshInterval * 1000);
            },

            stopRefreshInterval() {
                clearInterval(this.refreshTicker);
                this.refreshTicker = null;
            },
        },
    });
</script>
</body>
</html>
