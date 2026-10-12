<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'JIACRS - Jimma University')
    </title>

    {{-- AdminLTE 4 --}}
    <link rel="stylesheet"
          href="{{ asset('adminlte/css/adminlte.css') }}">

    {{-- JIACRS Custom CSS + Alpine --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <div class="app-wrapper">

        {{-- Dashboard Navigation --}}
        @include('layouts.navigation')

        {{-- Page Content --}}
        <main class="app-main">

            @isset($header)
                <div class="app-content-header">
                    <div class="container-fluid">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <div class="app-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>

        </main>

    </div>

    {{-- AdminLTE 4 --}}
    <script src="{{ asset('adminlte/js/adminlte.js') }}"></script>

</body>
</html>
