<header class="header header-sticky p-0 mb-4">
  <div class="container-fluid border-bottom px-4">
    <button class="header-toggler" type="button"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
            style="margin-inline-start: -14px;">
      <svg class="icon icon-lg">
        <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#cil-menu"></use>
      </svg>
    </button>

    {{-- POS Terminal Sell Button (Header Left) --}}
    @can('sales.create')
      <div class="d-flex align-items-center ms-2">
        <a class="btn btn-brand-sell btn-sm d-flex align-items-center gap-1.5 fw-bold px-3 py-1 shadow-sm"
           href="{{ route('pos.terminal') }}"
           aria-label="{{ __('messages.sell') }}"
           title="{{ __('messages.sell') }}">
          <i class="bi bi-cart-fill"></i>
          <span>{{ __('messages.sell') }}</span>
        </a>
      </div>
    @endcan

    <ul class="header-nav ms-auto align-items-center">
      {{-- Live clock --}}
      <li class="nav-item d-none d-lg-flex align-items-center me-3 text-body-secondary small">
        <i class="bi bi-clock me-1"></i>
        <span id="pos-header-clock" class="fw-semibold font-monospace">--:--:--</span>
      </li>

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

      {{-- Avatar & User Menu --}}
      <li class="nav-item dropdown">
        <a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button" aria-expanded="false">
          <div class="avatar avatar-md">
            <img class="avatar-img" src="{{ asset('admin/assets/img/avatars/8.jpg') }}" alt="{{ auth()->user()->name }}">
          </div>
        </a>

        <div class="dropdown-menu dropdown-menu-end pt-0 shadow-sm border" style="min-width: 220px; border-radius: 10px;">
          <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-1 py-2 px-3">
            <div class="fw-bold text-body fs-6">{{ auth()->user()->name }}</div>
            <small class="text-body-secondary">{{ config("access.roles." . auth()->user()->type . ".label") ?? ucfirst(str_replace('_', ' ', auth()->user()->type ?? 'Admin')) }}</small>
          </div>
          
          <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('profile.index') }}">
            <i class="bi bi-person me-2 text-primary fs-6"></i>
            <span>{{ __('messages.profile') }}</span>
          </a>

          <a class="dropdown-item d-flex align-items-center py-2" href="{{ route('profile.password') }}">
            <i class="bi bi-key me-2 text-warning fs-6"></i>
            <span>{{ __('messages.change_password') }}</span>
          </a>

          <a class="dropdown-item d-flex align-items-center py-2" href="#" onclick="event.preventDefault(); document.getElementById('clear-cache-form').submit();">
            <i class="bi bi-arrow-repeat me-2 text-success fs-6"></i>
            <span>{{ __('messages.clear_cache') }}</span>
          </a>
          <form id="clear-cache-form" action="{{ route('profile.clear-cache') }}" method="POST" class="d-none">
            @csrf
          </form>

          <div class="dropdown-divider my-1"></div>

          <a class="dropdown-item d-flex align-items-center py-2 text-danger"
            href="#"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="bi bi-box-arrow-right me-2 text-danger fs-6"></i>
              <span>{{ __('messages.logout') }}</span>
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
