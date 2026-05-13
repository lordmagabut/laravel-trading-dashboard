@extends('layout.master')

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

    .permission-group {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1rem;
        background: #f8fafc;
        height: 100%;
    }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="access-hero p-4 mb-4">
        <h4 class="mb-1">Edit Role</h4>
        <p class="text-muted mb-0">Perbarui role dan permission untuk {{ $role->name }}.</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card access-panel">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @foreach($permissions as $group => $items)
                        <div class="col-md-4">
                            <div class="permission-group">
                                <h6 class="mb-3 text-capitalize">{{ str_replace('-', ' ', $group) }}</h6>
                                @foreach($items as $permission)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="permission_{{ $permission->id }}"
                                               @checked(in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all()), true))>
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="submit" class="btn btn-primary me-2">Save Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-light">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
