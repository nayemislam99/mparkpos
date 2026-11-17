<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <h2 class="sidebar-brand-full">M POS</h2>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
            onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>

    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
        @foreach (config('access.menu', []) as $section)
            @php($visibleMenuItems = collect($section['items'])->filter(fn ($item) => auth()->user()->can($item['permission'])))
            @if ($visibleMenuItems->isNotEmpty())
                <li class="nav-title">{{ $section['heading'] }}</li>
                @include('admin.partials.sidebar-menu-items', ['items' => $visibleMenuItems->all()])
            @endif
        @endforeach
    </ul>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
