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
<div class="page-content">
    <div class="access-hero p-4 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-1">Roles</h4>
            <p class="text-muted mb-0">Role memegang permission. User cukup di-assign ke role yang tepat.</p>
        </div>
        <div>
            <a href="{{ route('roles.create') }}" class="btn btn-primary">Tambah Role</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card access-panel">
        <div class="card-body">
            <div class="table-responsive">
                <table id="rolesTable" class="table table-hover table-bordered align-middle nowrap access-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td>
                                    @if($role->permissions->isNotEmpty())
                                        <small class="text-muted">{{ $role->permissions->pluck('name')->implode(', ') }}</small>
                                    @else
                                        <span class="text-muted">No permissions</span>
                                    @endif
                                </td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit Permissions</a>
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" onsubmit="return confirm('Hapus role ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
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
        $('#rolesTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [3] }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            }
        });
    });
</script>
@endpush
