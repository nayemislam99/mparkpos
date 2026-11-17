<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  <meta name="description" content="@yield('meta_description', 'M POS administration dashboard')">
  <meta name="author" content="@yield('meta_author', 'M POS')">
  <meta name="keyword" content="@yield('meta_keyword', 'M POS,POS,Dashboard')">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'Admin')</title>

  {{-- Favicons --}}
  <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('admin/assets/favicon/apple-icon-57x57.png') }}" />
  <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('admin/assets/favicon/apple-icon-60x60.png') }}" />
  <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('admin/assets/favicon/apple-icon-72x72.png') }}" />
  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('admin/assets/favicon/apple-icon-76x76.png') }}" />
  <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('admin/assets/favicon/apple-icon-114x114.png') }}" />
  <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('admin/assets/favicon/apple-icon-120x120.png') }}" />
  <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('admin/assets/favicon/apple-icon-144x144.png') }}" />
  <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('admin/assets/favicon/apple-icon-152x152.png') }}" />
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('admin/assets/favicon/apple-icon-180x180.png') }}" />
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('admin/assets/favicon/android-icon-192x192.png') }}" />
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('admin/assets/favicon/favicon-32x32.png') }}" />
  <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('admin/assets/favicon/favicon-96x96.png') }}" />
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin/assets/favicon/favicon-16x16.png') }}" />
  <link rel="manifest" href="{{ asset('admin/assets/favicon/manifest.json') }}" />
  <meta name="theme-color" content="#ffffff" />

  {{-- CoreUI / Vendor CSS --}}
  <link rel="stylesheet" href="{{ asset('admin/vendors/simplebar/css/simplebar.css') }}" />
  <link rel="stylesheet" href="{{ asset('admin/css/vendors/simplebar.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


  {{-- Plugins CSS --}}
  <link rel="stylesheet" href="{{ asset('common/css/toastr.min.css') }}" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">


  {{-- Main CSS --}}
  <link rel="stylesheet" href="{{ asset('admin/css/style.css') }}" />
  <link rel="stylesheet" href="{{ asset('admin/css/examples.css') }}" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="{{ asset('common/css/yajra.dt.coreui.css') }}" />

  {{-- CoreUI config (before body) --}}
  <script src="{{ asset('admin/js/config.js') }}"></script>
  <script src="{{ asset('admin/js/color-modes.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  {{-- Page CSS --}}
  @stack('css')
</head>

<body>
  @include('admin.partials.sidebar')

  <div class="wrapper d-flex flex-column min-vh-100">
    @include('admin.partials.header')

    <div class="body flex-grow-1">
      @yield('content')
    </div>

    @include('admin.partials.footer')
  </div>

  {{-- Global JS variables --}}
  <script>
    window.csrf_token = @json(csrf_token());
    window.DT_BASE = "{{ url('/dashboard/dt') }}";
  </script>

  {{-- Core JS (jQuery MUST be before plugin JS) --}}
  <script src="{{ asset('common/js/jquery.min.js') }}"></script>

  {{-- CoreUI --}}
  <script src="{{ asset('admin/vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>
  <script src="{{ asset('admin/vendors/simplebar/js/simplebar.min.js') }}"></script>
  <script src="{{ asset('admin/js/main.js') }}"></script>

  <!-- app js -->
  <script src="{{ asset('common/js/app.js') }}"></script>

  {{-- Plugins JS --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.0/dist/sweetalert2.all.min.js"></script>
  <script src="{{ asset('common/js/toastr.min.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  {{-- DataTables (Bootstrap 5) --}}
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>


  {{-- Init scripts --}}
  <script src="{{ asset('common/js/toast_init.js') }}"></script>
  <script src="{{ asset('common/js/select_2_init.js') }}"></script>

  <!-- YOUR FACTORY -->
  <script src="{{ asset('common/js/yajra.dt.factory.js') }}"></script>

  {{-- Header shadow on scroll --}}
  {{-- <script>
    (function () {
      const header = document.querySelector('header.header');
      if (!header) return;
      document.addEventListener('scroll', () => {
        header.classList.toggle('shadow-sm', document.documentElement.scrollTop > 0);
      });
    })();
  </script> --}}

  {{-- Vendor JS per-page --}}
  @stack('vendor-js')

  {{-- Main app JS --}}
  <script src="{{ asset('admin/js/main.js') }}"></script>

  {{-- Toast include --}}
  @include('common.toast')

  {{-- Page JS --}}
  @stack('js')
</body>
</html>
