<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom py-3 px-3">
        <div class="sidebar-brand d-flex align-items-center justify-content-between w-100">
            <a href="{{ route('dashboard.index') }}" class="d-flex align-items-center text-decoration-none sidebar-brand-full py-1">
                <img src="{{ asset('admin/assets/img/mpos.png') }}" alt="{{ config('app.name', 'Micro POS') }}" style="max-height: 42px; max-width: 185px; object-fit: contain;">
            </a>
            <div class="sidebar-brand-narrow">
                <span class="badge bg-danger fw-bold fs-6">M</span>
            </div>
            <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
        </div>
    </div>

    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="">
        @foreach (config('access.menu', []) as $section)
            @php($visibleMenuItems = collect($section['items'])->filter(fn ($item) => auth()->user()->can($item['permission'])))
            @if ($visibleMenuItems->isNotEmpty())
                @include('admin.partials.sidebar-menu-items', ['items' => $visibleMenuItems->all()])
            @endif
        @endforeach
    </ul>

    <div class="sidebar-footer border-top d-none d-md-flex">
        <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
    </div>
</div>
