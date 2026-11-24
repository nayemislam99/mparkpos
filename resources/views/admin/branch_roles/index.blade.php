@extends('admin.layouts.dashboard')

@section('title', 'Branch User Role & Permissions | ' . config('app.name', 'M POS'))

@section('content')
    <div class="row">
        <div class="col-12 px-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-header bg-transparent border-0 d-flex flex-wrap align-items-center justify-content-between pt-4 px-4 pb-2">
                    <div>
                        <h3 class="fw-bold mb-0">Branch User Role & Permissions</h3>
                        <p class="text-body-secondary small mb-0">{{ $rolesCount }} roles</p>
                    </div>
                    @can('roles.update')
                        <button type="button" class="btn btn-success-pos d-flex align-items-center gap-1 px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addRoleModal" style="border-radius: 8px;">
                            <i class="bi bi-plus-lg"></i> Add New Role
                        </button>
                    @endcan
                </div>

                <div class="card-body px-4 pb-4">
                    {{-- Search bar (matching screenshot) --}}
                    <div class="row mb-3">
                        <div class="col-12 col-md-4">
                            <input type="text" id="searchRoleInput" class="form-control form-control-sm" placeholder="Search by role name" style="border-radius: 6px;">
                        </div>
                    </div>

                    {{-- Roles Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="rolesTable">
                            <thead>
                                <tr class="border-bottom">
                                    <th style="width: 70px;">SI</th>
                                    <th>Role Name</th>
                                    <th class="text-end" style="width: 220px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roleList as $index => $r)
                                    <tr class="role-row">
                                        <td class="text-body-secondary">{{ $index + 1 }}</td>
                                        <td class="fw-bold text-dark role-name-cell">{{ $r['name'] }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-teal px-3" onclick="openEditRoleModal('{{ $r['key'] }}', '{{ addslashes($r['name']) }}')" style="border-radius: 6px;">
                                                    Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-teal px-3" onclick="openPermissionsModal('{{ $r['key'] }}', '{{ addslashes($r['name']) }}')" style="border-radius: 6px;">
                                                    Permissions
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add New Role Modal --}}
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form method="POST" action="{{ route('branch-roles.role.store') }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="addRoleModalLabel">
                            <i class="bi bi-shield-plus me-1 text-primary"></i> Add New Role
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="newRoleName">Role Name <span class="text-danger">*</span></label>
                            <input type="text" id="newRoleName" name="role_name" class="form-control" placeholder="e.g. Due Collector" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success-pos px-4">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Role Modal --}}
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form method="POST" action="{{ route('branch-roles.role.store') }}">
                    @csrf
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold" id="editRoleModalLabel">
                            <i class="bi bi-pencil me-1 text-primary"></i> Edit Role Name
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <input type="hidden" id="editRoleKey" name="role_key">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="editRoleNameInput">Role Name <span class="text-danger">*</span></label>
                            <input type="text" id="editRoleNameInput" name="role_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-teal px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Permissions Matrix Modal (opens when clicking Permissions button) --}}
    <div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <form method="POST" action="{{ route('branch-roles.update') }}">
                    @csrf
                    <input type="hidden" id="permModalRole" name="role" value="">

                    <div class="modal-header border-bottom">
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="permissionsModalLabel">
                                <i class="bi bi-shield-check me-1 text-primary"></i> Permissions: <span id="permModalRoleName" class="text-primary"></span>
                            </h5>
                            <small class="text-body-secondary">Assign granular permissions for this role</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-end gap-2 mb-3 pb-2 border-bottom">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllModalPermissions(true)">
                                Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllModalPermissions(false)">
                                Deselect All
                            </button>
                        </div>

                        <div class="row g-3">
                            @foreach ($permissionGroups as $groupKey => $group)
                                <div class="col-12 col-md-6">
                                    <div class="border rounded p-3 bg-light h-100">
                                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                                            <span class="fw-bold small text-primary">
                                                <i class="bi bi-folder2-open me-1"></i> {{ $group['label'] }}
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" onclick="toggleModalGroup('{{ $groupKey }}')">
                                                <small class="text-secondary">Toggle</small>
                                            </button>
                                        </div>
                                        <div class="row g-2">
                                            @foreach ($group['permissions'] as $pKey => $pLabel)
                                                <div class="col-12">
                                                    <div class="form-check m-0">
                                                        <input class="form-check-input modal-perm-cb group-{{ $groupKey }}"
                                                               type="checkbox"
                                                               id="modal_p_{{ Str::slug($pKey) }}"
                                                               name="permissions[]"
                                                               value="{{ $pKey }}">
                                                        <label class="form-check-label small" for="modal_p_{{ Str::slug($pKey) }}">
                                                            {{ $pLabel }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-teal px-4">Save Permissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    // Search roles
    document.getElementById('searchRoleInput').addEventListener('input', function(e) {
        var term = (e.target.value || '').toLowerCase();
        document.querySelectorAll('.role-row').forEach(function(row) {
            var name = (row.querySelector('.role-name-cell').textContent || '').toLowerCase();
            row.style.display = name.includes(term) ? '' : 'none';
        });
    });

    window.openEditRoleModal = function(key, name) {
        document.getElementById('editRoleKey').value = key;
        document.getElementById('editRoleNameInput').value = name;
        var modal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        modal.show();
    };

    window.openPermissionsModal = function(roleKey, roleName) {
        document.getElementById('permModalRole').value = roleKey;
        document.getElementById('permModalRoleName').textContent = roleName;

        // Reset checkboxes
        document.querySelectorAll('.modal-perm-cb').forEach(function(cb) {
            cb.checked = false;
        });

        // Fetch permissions for this role
        fetch('{{ url('/dashboard/branch-roles/permissions') }}/' + roleKey, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var perms = data.permissions || [];
            var isAll = perms.includes('*');
            document.querySelectorAll('.modal-perm-cb').forEach(function(cb) {
                cb.checked = isAll || perms.includes(cb.value);
            });

            var modal = new bootstrap.Modal(document.getElementById('permissionsModal'));
            modal.show();
        })
        .catch(function() {
            alert('Failed to load role permissions.');
        });
    };

    function toggleAllModalPermissions(checked) {
        document.querySelectorAll('.modal-perm-cb').forEach(function(cb) {
            cb.checked = checked;
        });
    }

    function toggleModalGroup(groupKey) {
        var cbs = document.querySelectorAll('.modal-perm-cb.group-' + groupKey);
        var anyUnchecked = Array.from(cbs).some(function(cb) { return !cb.checked; });
        cbs.forEach(function(cb) { cb.checked = anyUnchecked; });
    }
</script>
@endpush
