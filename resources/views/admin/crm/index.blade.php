@extends('admin.layouts.dashboard')

@section('title', __('messages.all_crm') . ' | ' . config('app.name', 'Micro POS'))

@section('content')
<div class="row">
    <div class="col-12 px-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please check form inputs:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-4">
            {{-- Header matching Screenshot 3 --}}
            <div class="card-header bg-transparent border-bottom py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h4 class="mb-0 fw-bold text-body">{{ __('messages.all_crm') }}</h4>
                    <small class="text-body-secondary fw-semibold">
                        {{ __('messages.crm_count', ['count' => $crmCount]) }}
                        @if($deactivatedCount > 0)
                            &bull; <span class="text-danger">{{ $deactivatedCount }} Deactivated</span>
                        @endif
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('crm.index', array_merge(request()->query(), ['deactivated' => $showDeactivatedOnly ? 0 : 1])) }}"
                       class="btn btn-sm btn-teal text-white px-3 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-person-x"></i>
                        {{ $showDeactivatedOnly ? 'Show All CRM' : __('messages.deactivation_report') }}
                    </a>

                    @can('users.create')
                        <button type="button" class="btn btn-sm btn-success-pos text-white px-3 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm"
                                data-bs-toggle="modal" data-bs-target="#addCrmModal">
                            <i class="bi bi-plus-lg"></i> {{ __('messages.add_new_crm') }}
                        </button>
                    @endcan
                </div>
            </div>

            {{-- Filter and Search Bar --}}
            <div class="card-body pb-0 pt-3 border-bottom bg-light">
                <form action="{{ route('crm.index') }}" method="GET" class="row g-2 align-items-center mb-3">
                    @if($showDeactivatedOnly)
                        <input type="hidden" name="deactivated" value="1">
                    @endif
                    <div class="col-12 col-md-4 col-lg-3">
                        <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('messages.all_types_branches') }}</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ (string)$selectedBranchId === (string)$b->id ? 'selected' : '' }}>
                                    {{ $b->name }} {{ $b->is_head_office ? '(Head Office)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-8 col-lg-5 ms-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm"
                                   placeholder="{{ __('messages.search_crm_placeholder') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            @if($search !== '' || $selectedBranchId || $showDeactivatedOnly)
                                <a href="{{ route('crm.index') }}" class="btn btn-outline-danger" title="{{ __('messages.reset') }}">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- CRM Users Table (NO export buttons) --}}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small fw-semibold text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 50px;">SI</th>
                            <th>{{ __('messages.crm_name') }}</th>
                            <th>{{ __('messages.phone_email') }}</th>
                            <th>{{ __('messages.type_branch') }}</th>
                            <th>{{ __('messages.role_name') }}</th>
                            <th>{{ __('messages.created_at') }}</th>
                            <th>{{ __('messages.deactivated_at') }}</th>
                            <th class="text-end pe-3" style="min-width: 190px;">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($crmUsers as $index => $crm)
                            <tr>
                                <td class="ps-3 fw-semibold text-muted">
                                    {{ $crmUsers instanceof \Illuminate\Pagination\LengthAwarePaginator ? ($crmUsers->firstItem() + $index) : ($index + 1) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $crm->name }}</div>
                                    <small class="text-muted d-block">{{ $crm->address ?: '—' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $crm->phone ?: '—' }}</div>
                                    <small class="text-muted d-block">{{ $crm->email }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $crm->branch?->name ?: $company->name }}</div>
                                    <small class="text-muted d-block">
                                        {{ $crm->branch_id ? __('messages.branch_user') : __('messages.admin_helper') }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1">
                                        {{ $allRoles[$crm->type] ?? ucfirst(str_replace('_', ' ', $crm->type)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $crm->created_at?->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $crm->created_at?->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @if($crm->deactivated_at)
                                        <div class="small text-danger fw-semibold">{{ $crm->deactivated_at->format('d M Y') }}</div>
                                        <small class="text-muted">{{ $crm->deactivated_at->format('h:i A') }}</small>
                                    @else
                                        <span class="text-muted small">{{ __('messages.not_recorded') }}</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="d-inline-flex gap-1">
                                        @can('users.update')
                                            <button type="button" class="btn btn-sm btn-teal text-white edit-crm-btn"
                                                    data-id="{{ $crm->id }}"
                                                    data-bs-toggle="modal" data-bs-target="#editCrmModal">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <form action="{{ route('crm.users.status', $crm->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-teal text-white">
                                                    {{ $crm->is_active ? __('messages.deactivate') : __('messages.activate') }}
                                                </button>
                                            </form>
                                        @endcan

                                        @can('users.view')
                                            @if(auth()->id() !== $crm->id)
                                                <form action="{{ route('crm.users.login', $crm->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-teal text-white" title="{{ __('messages.login_as_user') }}">
                                                        {{ __('messages.login_as_user') }}
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                    {{ __('messages.no_records') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($crmUsers instanceof \Illuminate\Pagination\LengthAwarePaginator && $crmUsers->hasPages())
                <div class="card-footer bg-white py-3 border-top d-flex justify-content-end">
                    {{ $crmUsers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal: Add New CRM --}}
<div class="modal fade" id="addCrmModal" tabindex="-1" aria-labelledby="addCrmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('crm.users.store') }}">
            @csrf
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="addCrmModalLabel">
                    <i class="bi bi-person-plus me-2 text-success"></i>{{ __('messages.add_new_crm') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmName">{{ __('messages.crm_name') }} <span class="text-danger">*</span></label>
                        <input type="text" id="addCrmName" name="name" class="form-control" required placeholder="e.g. Moon Ahmed">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmPhone">{{ __('messages.branch_phone') }}</label>
                        <input type="text" id="addCrmPhone" name="phone" class="form-control" placeholder="e.g. 01849807777">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmEmail">{{ __('messages.branch_email') }} <span class="text-danger">*</span></label>
                        <input type="email" id="addCrmEmail" name="email" class="form-control" required placeholder="crm@example.com">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmPassword">Password <span class="text-danger">*</span></label>
                        <input type="password" id="addCrmPassword" name="password" class="form-control" required minlength="6" placeholder="Min. 6 characters">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmBranch">{{ __('messages.branch') }}</label>
                        <select id="addCrmBranch" name="branch_id" class="form-select">
                            <option value="">Head Office (Admin Helper)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="addCrmRole">{{ __('messages.role_name') }} <span class="text-danger">*</span></label>
                        <select id="addCrmRole" name="role" class="form-select" required>
                            @foreach($allRoles as $roleKey => $roleLabel)
                                <option value="{{ $roleKey }}" {{ $roleKey === 'manager' ? 'selected' : '' }}>
                                    {{ $roleLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="addCrmAddress">{{ __('messages.branch_address') }}</label>
                        <input type="text" id="addCrmAddress" name="address" class="form-control" placeholder="e.g. 163, Nawabpur Road, Dhaka-1000">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="addCrmActive" name="is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="addCrmActive">{{ __('messages.active') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-success-pos text-white btn-sm px-4 fw-semibold">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Edit CRM --}}
<div class="modal fade" id="editCrmModal" tabindex="-1" aria-labelledby="editCrmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content border-0 shadow" id="editCrmForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold text-dark" id="editCrmModalLabel">
                    <i class="bi bi-pencil-square me-2 text-teal"></i>{{ __('messages.edit_crm') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmName">{{ __('messages.crm_name') }} <span class="text-danger">*</span></label>
                        <input type="text" id="editCrmName" name="name" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmPhone">{{ __('messages.branch_phone') }}</label>
                        <input type="text" id="editCrmPhone" name="phone" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmEmail">{{ __('messages.branch_email') }} <span class="text-danger">*</span></label>
                        <input type="email" id="editCrmEmail" name="email" class="form-control" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmPassword">Password</label>
                        <input type="password" id="editCrmPassword" name="password" class="form-control" minlength="6" placeholder="{{ __('messages.password_placeholder') }}">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmBranch">{{ __('messages.branch') }}</label>
                        <select id="editCrmBranch" name="branch_id" class="form-select">
                            <option value="">Head Office (Admin Helper)</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="editCrmRole">{{ __('messages.role_name') }} <span class="text-danger">*</span></label>
                        <select id="editCrmRole" name="role" class="form-select" required>
                            @foreach($allRoles as $roleKey => $roleLabel)
                                <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold" for="editCrmAddress">{{ __('messages.branch_address') }}</label>
                        <input type="text" id="editCrmAddress" name="address" class="form-control">
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="editCrmActive" name="is_active" value="1">
                            <label class="form-check-label fw-semibold" for="editCrmActive">{{ __('messages.active') }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn btn-teal text-white btn-sm px-4 fw-semibold">{{ __('messages.update') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editCrmButtons = document.querySelectorAll('.edit-crm-btn');
    const editForm = document.getElementById('editCrmForm');

    editCrmButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const userId = this.getAttribute('data-id');
            editForm.action = `/dashboard/crm/users/${userId}`;

            fetch(`/dashboard/crm/users/${userId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                const user = data.user;
                document.getElementById('editCrmName').value = user.name || '';
                document.getElementById('editCrmPhone').value = user.phone || '';
                document.getElementById('editCrmEmail').value = user.email || '';
                document.getElementById('editCrmPassword').value = '';
                document.getElementById('editCrmAddress').value = user.address || '';
                document.getElementById('editCrmBranch').value = user.branch_id || '';
                document.getElementById('editCrmRole').value = user.type || 'manager';
                document.getElementById('editCrmActive').checked = Boolean(user.is_active);
            })
            .catch(err => console.error('Failed to load CRM user details', err));
        });
    });
});
</script>
@endpush
