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
        <h4 class="mb-1">Tambah User</h4>
        <p class="text-muted mb-0">Buat user baru dan assign role awalnya.</p>
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
            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="permission-group">
                            <h6 class="mb-3">Initial Role</h6>
                            <select name="role" class="form-select">
                                <option value="">No role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">Permission akan diwariskan dari role yang dipilih.</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary me-2">Simpan User</button>
                <a href="{{ route('users.index') }}" class="btn btn-light">Back</a>
            </form>
        </div>
    </div>
</div>
@endsection
