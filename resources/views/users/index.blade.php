@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@push('style')
<style>
    .access-hero,
    .access-panel {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .access-panel {
        background: #fff;
    }

    .access-summary {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    }

    .access-summary .card-body {
        min-height: 120px;
    }

    .summary-label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
        font-weight: 700;
    }

    .summary-value {
        margin-top: .75rem;
        margin-bottom: 0;
        font-size: 2.25rem;
        font-weight: 700;
        color: #0f172a;
    }

    .access-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
</style>
@endpush

@section('content')
@php
    $adminCount = $users->filter(fn($user) => $user->roles->contains('name', 'admin') || $user->roles->contains('name', 'super-admin'))->count();
@endphp
<div class="page-content">
    <div class="access-hero p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
        <h4 class="mb-1">Users</h4>
        <p class="text-muted mb-0">Kelola user dan assign role untuk Laravel Trading Dashboard.</p>
        </div>
        <div>
            <a href="{{ route('users.create') }}" class="btn btn-primary">Tambah User</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card access-summary">
                <div class="card-body">
                    <div class="summary-label">Total Users</div>
                    <h3 class="summary-value">{{ $users->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card access-summary">
                <div class="card-body">
                    <div class="summary-label">Defined Roles</div>
                    <h3 class="summary-value">{{ $roles->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card access-summary">
                <div class="card-body">
                    <div class="summary-label">Admin Users</div>
                    <h3 class="summary-value">{{ $adminCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card access-panel mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama atau email">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card access-panel">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover table-bordered align-middle nowrap access-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Direct Permissions</th>
                            <th>Created</th>
                            <th>Role Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td><strong>{{ $user->name }}</strong></td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-primary">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">No role</span>
                                    @endforelse
                                </td>
                                <td><span class="text-muted">Inherited from role</span></td>
                                <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Change Role</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
    $(function () {
        $('#usersTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [5] }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            }
        });
    });
</script>
@endpush
