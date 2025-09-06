@extends('layouts.panel')

@section('title', 'Domain Details')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>{{ __('Domain Details') }}</h4>
                    <a href="{{ route('domains.index') }}" class="btn btn-secondary btn-sm float-end">{{ __('Back to Domains') }}</a>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label text-md-end">{{ __('Domain Name') }}</label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $domain->name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label text-md-end">{{ __('Document Root') }}</label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $domain->document_root ?? '/public_html' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label text-md-end">{{ __('Status') }}</label>
                        <div class="col-md-9">
                            <span class="badge bg-{{ $domain->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($domain->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label text-md-end">{{ __('Created') }}</label>
                        <div class="col-md-9">
                            <p class="form-control-plaintext">{{ $domain->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    @if(auth()->user()->id === $domain->user_id)
                    <div class="row">
                        <div class="col-md-9 offset-md-3">
                            <a href="{{ route('domains.edit', $domain) }}" class="btn btn-primary">{{ __('Edit Domain') }}</a>
                            <form method="POST" action="{{ route('domains.destroy', $domain) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this domain?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">{{ __('Delete Domain') }}</button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
