@foreach ($items as $item)
    @can($item['permission'])
        @php($activeRoutes = $item['active_routes'] ?? (isset($item['route']) ? [$item['route']] : []))
        @php($isActive = collect($activeRoutes)->contains(fn ($route) => request()->routeIs($route)))

        @if (!empty($item['children']))
            <li class="nav-group {{ $isActive ? 'show' : '' }}">
                <a class="nav-link nav-group-toggle {{ $isActive ? 'active' : '' }}" href="#"
                    aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                    @if (!empty($item['icon']))
                        <svg class="nav-icon">
                            <use xlink:href="{{ asset('admin/vendors/@coreui/icons/svg/free.svg') }}#{{ $item['icon'] }}"></use>
                        </svg>
                    @else
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                    @endif
                    {{ $item['label'] }}
                </a>
                <ul class="nav-group-items compact">
                    @include('admin.partials.sidebar-menu-items', ['items' => $item['children']])
                </ul>
            </li>
        @elseif (!empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']))
            <li class="nav-item">
                <a class="nav-link {{ $isActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                    <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                    {{ $item['label'] }}
                </a>
            </li>
        @else
            <li class="nav-item">
                <span class="nav-link disabled" aria-disabled="true">
                    <span class="nav-icon"><span class="nav-icon-bullet"></span></span>
                    {{ $item['label'] }}
                    @if (!empty($item['soon']))
                        <span class="badge badge-sm bg-secondary ms-auto">Soon</span>
                    @endif
                </span>
            </li>
        @endif
    @endcan
@endforeach
