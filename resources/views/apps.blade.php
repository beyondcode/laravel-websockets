@extends('websockets::layout')

@section('title')
  Apps
@endsection

@section('content')
<div class="flex flex-col py-8" id="app">
  <form action="{{ route('laravel-websockets.apps.store') }}" method="POST">
    @csrf
    <div>
      <div>
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Add new app
          </h3>
        </div>

        @if($errors->isNotEmpty())
          <div class="bg-red-500 text-white my-4 p-4 rounded">
            @foreach($errors->all() as $error)
              {{ $error }}<br>
            @endforeach
          </div>
        @endif

        <div class="mt-6 sm:mt-5">
          <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:border-t sm:border-gray-200 sm:pt-5">
            <label for="name"
                   class="block text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
              Name
            </label>
            <div class="mt-1 sm:mt-0 sm:col-span-2">
              <div class="max-w-lg flex rounded-md shadow-sm">
                <input id="name" name="name"
                       class="flex-1 form-input block w-full rounded-md transition duration-150 ease-in-out sm:text-sm sm:leading-5"/>
              </div>
            </div>
          </div>
          <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5">
            <label for="allowed_origins"
                   class="block text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
              Allowed origins (comma separated)
            </label>
            <div class="mt-1 sm:mt-0 sm:col-span-2">
              <div class="max-w-lg flex rounded-md shadow-sm">
                <input id="allowed_origins" name="allowed_origins"
                       class="flex-1 form-input block w-full rounded-md transition duration-150 ease-in-out sm:text-sm sm:leading-5"/>
              </div>
            </div>
          </div>
          <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5">
            <label for="enable_statistics"
                   class="block text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
              Enable Statistics
            </label>
            <div class="mt-1 sm:mt-0 sm:col-span-2">
              <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center">
                  <input id="enable_statistics"
                         name="enable_statistics"
                         value="1" type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out" />
                  <label for="enable_statistics" class="ml-2 block text-sm leading-5 text-gray-900">
                    Yes
                  </label>
                </div>
              </div>
            </div>
          </div>
          <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start sm:pt-5">
            <label for="enable_client_messages"
                   class="block text-sm font-medium leading-5 text-gray-700 sm:mt-px sm:pt-2">
              Enable Client Messages
            </label>
            <div class="mt-1 sm:mt-0 sm:col-span-2">
              <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center">
                  <input id="enable_client_messages"
                         name="enable_client_messages"
                         value="1" type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 transition duration-150 ease-in-out" />
                  <label for="enable_client_messages" class="ml-2 block text-sm leading-5 text-gray-900">
                    Yes
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8 border-t border-gray-200 pt-5">
          <div class="flex justify-end">
                            <span class="ml-3 inline-flex rounded-md shadow-sm">
        <button type="submit"
                @click.prevent="saveUser"
                class="inline-flex justify-center py-2 px-4 border border-transparent text-sm leading-5 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-700 transition duration-150 ease-in-out">
          Save
        </button>
      </span>
          </div>
        </div>
      </div>
    </div>
  </form>
  <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div
      class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200">
      <table class="min-w-full" v-if="apps.length > 0">
        <thead>
        <tr>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
            Name
          </th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
            Allowed origins
          </th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
            Statistics
          </th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
            Client Messages
          </th>
          <th class="px-6 py-3 border-b border-gray-200 bg-gray-50"></th>
        </tr>
        </thead>
        <tbody class="bg-white">
          <tr v-for="app in apps">
            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 font-medium text-gray-900">
              @{{ app.name }}
            </td>
            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">
              @{{ app.allowed_origins || '*' }}
            </td>
            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">
              <span v-if="app.enable_statistics">Yes</span>
              <span v-else>No</span>
            </td>
            <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 text-sm leading-5 text-gray-500">
              <span v-if="app.enable_client_messages">Yes</span>
              <span v-else>No</span>
            </td>
            <td class="px-6 py-4 whitespace-no-wrap text-right border-b border-gray-200 text-sm leading-5 font-medium">
              <a href="#" @click.prevent="showInstructions(app)"
                 class="pl-4 text-gray-600 hover:text-gray-900">Installation instructions</a>
              <a href="#" @click.prevent="deleteUser(user)"
                 class="pl-4 text-red-600 hover:text-red-900">Delete</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="py-4" v-if="app">
    <p class="pb-1">Modify your <code>.env</code> file:</p>
    <pre class="bg-gray-100 p-4 rounded">PUSHER_APP_HOST=@{{ app.host === null ? window.location.hostname : app.host }}
PUSHER_APP_PORT={{ $port }}
PUSHER_APP_KEY=@{{ app.key }}
PUSHER_APP_ID=@{{ app.id }}
PUSHER_APP_SECRET=@{{ app.secret }}
PUSHER_APP_SCHEME=https
MIX_PUSHER_APP_HOST="${PUSHER_APP_HOST}"
MIX_PUSHER_APP_PORT="${PUSHER_APP_PORT}"
MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"</pre>
  </div>

</div>
@endsection

@section('scripts')
  <script>
    new Vue({
      el: '#app',
      data: {
        app: null,
        apps: @json($apps),
      },
      methods: {
        showInstructions(app) {
          this.app = app;
        }
      }
    });
  </script>
@endsection
