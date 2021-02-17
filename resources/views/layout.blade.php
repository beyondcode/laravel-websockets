<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

  <title>Laravel WebSockets</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tailwindcss/ui@latest/dist/tailwind-ui.min.css">
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

<body>
<div class="min-h-screen bg-white">
  <nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center font-bold">
            Laravel WebSockets
          </div>
          <div class="hidden sm:-my-px sm:ml-6 sm:flex">
            <a href="{{ route('laravel-websockets.dashboard') }}"
               class="
               @if(Route::is('laravel-websockets.dashboard'))
                 border-indigo-500 focus:border-indigo-700 text-gray-900
              @else
                border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300
               @endif
                           inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5  focus:outline-none  transition duration-150 ease-in-out">
              Dashboard
            </a>
            <a href="{{ route('laravel-websockets.apps') }}"
               class="
               @if(Route::is('laravel-websockets.apps'))
                 border-indigo-500 focus:border-indigo-700 text-gray-900
              @else
                 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300
              @endif
                           ml-8 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out">
              Apps
            </a>
          </div>
        </div>
        <div class="-mr-2 flex items-center sm:hidden">
          <!-- Mobile menu button -->
          <button
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
            <!-- Menu open: "hidden", Menu closed: "block" -->
            <svg class="block h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <!-- Menu open: "block", Menu closed: "hidden" -->
            <svg class="hidden h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </nav>


  <div class="py-10">
    <header>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold leading-tight text-gray-900">
          @yield('title')
        </h1>
      </div>
    </header>
    <main>
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @yield('content')
      </div>
    </main>
  </div>
</div>

@yield('scripts')
</body>
</html>
