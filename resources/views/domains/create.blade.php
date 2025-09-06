@extends('layouts.panel')

@section('title', 'Tambah Domain - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">Tambah Domain Baru</h1>
    <a href="{{ route('domains.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Tambah Domain</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('domains.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Domain</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="contoh: example.com" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Masukkan nama domain yang valid (contoh: example.com, subdomain.example.com)
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6><i class="fas fa-info-circle text-info me-2"></i>Informasi Domain</h6>
                                <ul class="mb-0 small">
                                    <li>Domain harus berformat yang valid (contoh: example.com)</li>
                                    <li>Subdomain diperbolehkan (contoh: blog.example.com)</li>
                                    <li>Domain tidak boleh duplikat</li>
                                    <li>Pastikan domain sudah diarahkan ke server ini</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('domains.index') }}" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Simpan Domain
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
