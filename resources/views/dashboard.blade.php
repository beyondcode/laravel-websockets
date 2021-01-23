<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

  <title>WebSockets Dashboard</title>

  <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/vue-json-editor@1.4.2/assets/jsoneditor.min.css" rel="stylesheet">

  <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>
  <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/v-jsoneditor@1.4.1/dist/v-jsoneditor.min.js"></script>

  <script>
    window.baseURL = '{{ url(request()->path()) }}';
    axios.defaults.baseURL = baseURL;
  </script>
</head>

<body class="px-6">
  <div
    id="app"
    class="mx-auto"
    :class="{
      'max-w-xl': ! connected,
      'max-w-6xl': connected,
    }"
  >
    <div class="w-full my-6 rounded-lg bg-gray-100 p-6">
      <div class="font-semibold uppercase text-gray-700 mb-6">
        Connect to app
      </div>

      <div class="flex flex-row justify-between">
        <div class="relative">
          <select
            v-model="app"
            class="block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-2 px-6 pr-12 rounded-lg focus:outline-none focus:bg-white focus:border-gray-500"
            id="grid-state"
          >
            <option
              v-for="app in apps"
              :value="app"
            >
              @{{ app.name }}
            </option>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>
        <div>
          <button
            v-if="connected"
            @click.prevent="disconnect"
            class="bg-red-500 hover:bg-red-600 rounded-full px-3 py-2 text-white focus:outline-none"
          >
            Disconnect
          </button>

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
        </div>
      </div>
    </div>

    <div
      v-if="connected && app.statisticsEnabled"
      class="w-full my-6 px-6"
    >
      <div class="flex justify-between items-center">
        <span class="font-semibold uppercase text-gray-700">
          Live statistics
        </span>

        <div class="space-x-3 flex items-center">
          <div>
            <input
              type="checkbox"
              v-model="autoRefresh"
              class="mr-2"
            />
            Refresh automatically
          </div>

          <button
            @click="loadChart"
            class="rounded-full bg-blue-500 hover:bg-blue-600 focus:outline-none text-white px-3 py-1"
          >
            Refresh
          </button>
        </div>
      </div>

      <div
        id="statisticsChart"
        style="width: 100%; height: 250px;"
      ></div>
    </div>

    <div
      v-if="connected"
      class="flex flex-col rounded-lg bg-gray-100 p-6 my-6 space-y-6"
    >
      <div class="font-semibold uppercase text-gray-700">
        Send payload event to channel
      </div>

      <div class="flex flex-col space-y-6 md:flex-row md:space-x-6 md:space-y-0">
        <div class="w-full md:w-1/2">
          <label
            class="block text-gray-700 text-sm font-bold mb-2"
            for="channel"
          >
            Channel name
          </label>
          <input
            v-model="form.channel"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="channel"
            type="text"
            placeholder="ex: orders"
          >
        </div>
        <div class="w-full md:w-1/2">
          <label
            class="block text-gray-700 text-sm font-bold mb-2"
            for="event"
          >
            Event name
          </label>
          <input
            v-model="form.event"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="event"
            type="text"
            placeholder="ex: OrderShipped"
          >
        </div>
      </div>

      <div>
        <label class="block text-gray-700 text-sm font-bold mb-2">
          Payload
        </label>

        <v-jsoneditor
          v-model="form.data"
          :options="{ mode: 'code' }"
        />
      </div>

      <div>
        <button
          @click.prevent="sendEvent"
          class="rounded-full px-3 py-2 text-white focus:outline-none"
          :class="{
            'bg-blue-500 hover:bg-blue-600': ! sendingEvent,
            'bg-gray-500 cursor-not-allowed': sendingEvent,
          }"
        >
          <template v-if="sendingEvent">
            Sending...
          </template>
          <template v-else>
            Send event
          </template>
        </button>
      </div>
    </div>

    <div
      v-if="connected"
      class="flex flex-col my-6"
    >
      <div class="font-semibold uppercase text-gray-700 mb-6">
        Server activity
      </div>

      <div class="overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
          <table class="min-w-full divide-y divide-gray-200">
            <thead>
              <tr>
                <th class="px-6 py-3 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th class="px-6 py-3 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Details
                </th>
                <th class="px-6 py-3 border-b border-gray-200 bg-gray-100 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                  Time
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="(log, index) in logs.slice().reverse()"
                :key="index"
              >
                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                  <div
                    :class="[getBadgeClass(log)]"
                    class="rounded-full px-3 py-1 inline-block text-sm"
                  >
                    @{{ log.type }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                  <pre class="text-xs">@{{ log.details }}</pre>
                </td>
                <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                  @{{ log.time }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
<script>
  new Vue({
    el: '#app',
    data: {
      connected: false,
      connecting: false,
      sendingEvent: false,
      autoRefresh: true,
      refreshInterval: {{ $refreshInterval }},
      refreshTicker: null,
      chart: null,
      pusher: null,
      app: null,
      apps: @json($apps),
      form: {
        channel: null,
        event: null,
        data: {},
      },
      logs: [],
    },
    mounted () {
      this.app = this.apps[0] || null;
    },
    destroyed () {
      if (this.refreshTicker) {
        this.stopRefreshInterval();
      }
    },
    watch: {
      connected (newVal) {
        newVal ? this.startRefreshInterval() : this.stopRefreshInterval();
      },
      autoRefresh (newVal) {
        newVal ? this.startRefreshInterval() : this.stopRefreshInterval();
      },
    },
    methods: {
      connect () {
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

          if (this.app.statisticsEnabled) {
            this.loadChart();
          }
        });

        this.pusher.connection.bind('disconnected', () => {
          this.connected = false;
          this.connecting = false;
          this.logs = [];
          this.chart = null;
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

      disconnect () {
        this.pusher.disconnect();
        this.connecting = false;
        this.chart = null;
      },

      loadChart () {
        axios.get(`/api/${this.app.id}/statistics`)
          .then(res => {
            let data = res.data;

            let chartData = [
              {
                x: data.peak_connections.x,
                y: data.peak_connections.y,
                type: 'lines',
                name: '# Peak Connections'
              },
              {
                x: data.websocket_messages_count.x,
                y: data.websocket_messages_count.y,
                type: 'bar',
                name: '# Websocket Messages'
              },
              {
                x: data.api_messages_count.x,
                y: data.api_messages_count.y,
                type: 'bar',
                name: '# API Messages'
              },
            ];

            let layout = {
              margin: {
                l: 50,
                r: 0,
                b: 50,
                t: 50,
                pad: 4,
              },
              autosize: true,
            };

            this.chart = this.chart
              ? Plotly.react('statisticsChart', chartData, layout)
              : Plotly.newPlot('statisticsChart', chartData, layout);

        });
      },

      subscribeToAllChannels () {
        @json($channels).forEach(channelName => this.subscribeToChannel(channelName))
      },

      subscribeToChannel (channel) {
        this.pusher.subscribe(`{{ $logPrefix }}${channel}`)
          .bind('log-message', (data) => {
            this.logs.push(data);
          });
      },

      sendEvent () {
        if (! this.sendingEvent) {
          this.sendingEvent = true;

          let payload = {
            _token: '{{ csrf_token() }}',
            appId: this.app.id,
            key: this.app.key,
            secret: this.app.secret,
            channel: this.form.channel,
            event: this.form.event,
            data: JSON.stringify(this.form.data),
          };

          axios
            .post('/event', payload)
            .then(() => {})
            .catch(err => {
              alert('Error sending event.');
            })
            .then(() => {
              this.sendingEvent = false;
            });
        }
      },

      getBadgeClass (log) {
        if (['connection', 'subscribed'].includes(log.type)) {
          return 'bg-green-500 text-white';
        }

        if (['replicator-subscribed'].includes(log.type)) {
          return 'bg-green-700 text-white';
        }

        if (['disconnection', 'replicator-unsubscribed'].includes(log.type)) {
          return 'bg-red-700 text-white';
        }

        if (['api_message', 'replicator-message-received'].includes(log.type)) {
          return 'bg-black text-white';
        }

        return 'bg-gray-700 text-white';
      },

      startRefreshInterval () {
        this.refreshTicker = setInterval(function () {
            this.loadChart();
          }.bind(this), this.refreshInterval * 1000);
      },

      stopRefreshInterval () {
        clearInterval(this.refreshTicker);
        this.refreshTicker = null;
      },
    },
  });
</script>
</body>
</html>
