@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h4>
                        <i class="fas fa-shield-alt"></i>
                        Two-Factor Authentication
                    </h4>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="text-center mb-4">
                        <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                        <p class="mt-3">Please enter the 6-digit code from your authenticator app.</p>
                    </div>

                    <form method="POST" action="{{ route('2fa.verify') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="one_time_password" class="form-label">Verification Code</label>
                            <input type="text" class="form-control form-control-lg text-center" 
                                   id="one_time_password" name="one_time_password" 
                                   maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="000000" autofocus required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check"></i>
                                Verify
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-link">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
