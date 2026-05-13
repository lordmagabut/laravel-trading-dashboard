@extends('layout.master2')

@section('content')
<div class="page-content d-flex align-items-center justify-content-center">

  <div class="row w-100 mx-0 auth-page">
    <div class="col-md-8 col-xl-6 mx-auto">
      <div class="card">
        <div class="row">
          <div class="col-md-4 pe-md-0">
            <div class="auth-side-wrapper" style="background-image: url({{ url('https://via.placeholder.com/219x452') }})">

            </div>
          </div>
          <div class="col-md-8 ps-md-0">
            <div class="auth-form-wrapper px-4 py-5">
              <a href="#" class="noble-ui-logo d-block mb-2">Trading<span>Dash</span></a>
              <h5 class="text-muted fw-normal mb-4">Buat akun baru untuk masuk ke dashboard.</h5>
              <form class="forms-sample" method="POST" action="{{ route('register.store') }}">
                @csrf

                @if($errors->any())
                  <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                      @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <div class="mb-3">
                  <label for="exampleInputUsername1" class="form-label">Nama</label>
                  <input type="text" class="form-control" id="exampleInputUsername1" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Nama lengkap" required>
                </div>
                <div class="mb-3">
                  <label for="userEmail" class="form-label">Email address</label>
                  <input type="email" class="form-control" id="userEmail" name="email" value="{{ old('email') }}" placeholder="Email" required>
                </div>
                <div class="mb-3">
                  <label for="userPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="userPassword" name="password" autocomplete="new-password" placeholder="Password" required>
                </div>
                <div class="mb-3">
                  <label for="userPasswordConfirmation" class="form-label">Konfirmasi Password</label>
                  <input type="password" class="form-control" id="userPasswordConfirmation" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password" required>
                </div>
                <div>
                  <button type="submit" class="btn btn-primary me-2 mb-2 mb-md-0">Sign up</button>
                </div>
                <a href="{{ route('login') }}" class="d-block mt-3 text-muted">Sudah punya akun? Login</a>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
