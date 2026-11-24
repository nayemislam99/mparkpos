<header class="header header-sticky p-0 mb-4">
  <div class="container-fluid border-bottom px-4">
    <button class="header-toggler" type="button"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
            style="margin-inline-start: -14px;">
      <svg class="icon icon-lg">
        <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-menu"></use>
      </svg>
    </button>

    <div class="d-none d-md-flex align-items-center ms-2">
      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 small">
        <i class="bi bi-shop me-1"></i>
        {{ auth()->user()->branch->name ?? __('messages.all_branches') }}
      </span>
    </div>

    <ul class="header-nav ms-auto align-items-center">
      {{-- Live clock --}}
      <li class="nav-item d-none d-lg-flex align-items-center me-3 text-body-secondary small">
        <i class="bi bi-clock me-1"></i>
        <span id="pos-header-clock" class="fw-semibold font-monospace">--:--:--</span>
      </li>

      {{-- Quick Sell button --}}
      @can('sales.create')
        <li class="nav-item me-2">
          <a class="btn btn-warning btn-sm d-flex align-items-center gap-1 text-dark fw-bold px-3 py-1 shadow-sm"
             href="{{ route('pos.terminal') }}"
             aria-label="{{ __('messages.sell') }}"
             title="{{ __('messages.sell') }}">
            <i class="bi bi-cart-fill"></i>
            <span>{{ __('messages.sell') }}</span>
          </a>
        </li>
      @endcan

      {{-- Language toggle --}}
      <li class="nav-item dropdown me-2">
        <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center gap-1" type="button"
                aria-label="{{ __('messages.language') }}" title="{{ __('messages.language') }}"
                aria-expanded="false" data-coreui-toggle="dropdown">
          <i class="bi bi-translate fs-5"></i>
          <span class="small fw-semibold d-none d-sm-inline">{{ app()->getLocale() === 'bn' ? 'বাংলা' : 'English' }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 8rem;">
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between {{ app()->getLocale() === 'en' ? 'active' : '' }}"
               href="{{ route('locale.switch', 'en') }}">
              <span>English</span>
              @if(app()->getLocale() === 'en') <i class="bi bi-check-lg"></i> @endif
            </a>
          </li>
          <li>
            <a class="dropdown-item d-flex align-items-center justify-content-between {{ app()->getLocale() === 'bn' ? 'active' : '' }}"
               href="{{ route('locale.switch', 'bn') }}">
              <span>বাংলা</span>
              @if(app()->getLocale() === 'bn') <i class="bi bi-check-lg"></i> @endif
            </a>
          </li>
        </ul>
      </li>

      {{-- Theme toggle --}}
      <li class="nav-item dropdown">
        <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button"
                aria-label="Choose color theme" title="Choose color theme"
                aria-expanded="false" data-coreui-toggle="dropdown">
          <svg class="icon icon-lg theme-icon-active">
            <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-contrast"></use>
          </svg>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 8rem;">
          <li>
            <button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="light">
              <svg class="icon icon-lg me-3"><use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-sun"></use></svg>
              Light
            </button>
          </li>
          <li>
            <button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="dark">
              <svg class="icon icon-lg me-3"><use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-moon"></use></svg>
              Dark
            </button>
          </li>
          <li>
            <button class="dropdown-item d-flex align-items-center active" type="button" data-coreui-theme-value="auto">
              <svg class="icon icon-lg me-3"><use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-contrast"></use></svg>
              Auto
            </button>
          </li>
        </ul>
      </li>

      <li class="nav-item py-1"><div class="vr h-100 mx-2 text-body text-opacity-75"></div></li>

      {{-- Avatar --}}
      <li class="nav-item dropdown">
        <a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button">
          <div class="avatar avatar-md">
            <img class="avatar-img" src="{{ asset('admin/assets/img/avatars/8.jpg') }}" alt="{{ auth()->user()->name }}">
          </div>
        </a>

        <div class="dropdown-menu dropdown-menu-end pt-0">
          <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">
            {{ auth()->user()->name }}<br>
            <span class="fw-normal">{{ auth()->user()->email }}</span>
          </div>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item"
            href="#"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <svg class="icon me-2">
                  <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg#cil-account-logout') }}"></use>
              </svg>
              {{ __('messages.logout') }}
          </a>

          <form id="logout-form"
                action="{{ route('auth.logout') }}"
                method="POST"
                class="d-none">
              @csrf
          </form>

        </div>
      </li>
    </ul>
  </div>
</header>
<script>
  (function() {
    function tick() {
      var el = document.getElementById('pos-header-clock');
      if (el) {
        var d = new Date();
        el.textContent = d.toLocaleTimeString();
      }
    }
    setInterval(tick, 1000);
    tick();
  })();
</script>
