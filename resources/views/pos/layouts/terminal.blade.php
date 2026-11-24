<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f4f6fb">
    <title>@yield('title', 'POS terminal | Micro POS')</title>

    <link rel="stylesheet" href="{{ asset('admin/css/vendors/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="{{ asset('admin/js/config.js') }}"></script>
    <script src="{{ asset('admin/js/color-modes.js') }}"></script>
    @stack('css')
</head>
<body class="pos-standalone">
    @yield('content')

    <script src="{{ asset('common/js/jquery.min.js') }}"></script>
    <script src="{{ asset('admin/vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>
    <script src="{{ asset('common/js/app.js') }}"></script>
    @stack('vendor-js')
    @stack('js')
</body>
</html>
