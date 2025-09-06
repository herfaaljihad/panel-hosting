@extends('layouts.panel')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt"></i>
                        Two-Factor Authentication
                    </h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($user->google2fa_enabled)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Two-Factor Authentication is <strong>enabled</strong> on your account.
                        </div>

                        <form method="POST" action="{{ route('2fa.disable') }}" class="mt-3">
                            @csrf
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Enter your password to disable 2FA</div>
                            </div>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times"></i>
                                Disable 2FA
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Two-Factor Authentication is <strong>disabled</strong> on your account.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Setup Instructions:</h5>
                                <ol>
                                    <li>Install an authenticator app (Google Authenticator, Authy, etc.)</li>
                                    <li>Scan the QR code with your authenticator app</li>
                                    <li>Enter the 6-digit code from your app</li>
                                    <li>Click "Enable 2FA" to activate</li>
                                </ol>

                                <form method="POST" action="{{ route('2fa.enable') }}" class="mt-3">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="one_time_password" class="form-label">Verification Code</label>
                                        <input type="text" class="form-control" id="one_time_password" name="one_time_password" 
                                               maxlength="6" pattern="[0-9]{6}" required>
                                        <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i>
                                        Enable 2FA
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6 text-center">
                                <h5>QR Code:</h5>
                                <div class="qr-code-container" style="background: white; padding: 20px; border-radius: 10px; display: inline-block;">
                                    {!! $qrCode !!}
                                </div>
                                <p class="mt-2 text-muted">
                                    <small>Secret Key: {{ $user->google2fa_secret }}</small>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
