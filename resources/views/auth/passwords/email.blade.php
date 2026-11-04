<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lupa Password - {{ config('app.name', 'ERP System') }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/bgs-global.css') }}?v={{ filemtime(public_path('css/bgs-global.css')) }}">
</head>
<body class="bg-light bgs-ui bgs-page-auth-passwords-email bgs-mode-create">
<main class="container py-4"><div class="row justify-content-center min-vh-100 align-items-center"><div class="col-12 col-md-6 col-lg-4">
<div class="card shadow"><div class="card-header bg-primary text-white text-center bgs-page-header"><h4>{{ config('app.name', 'ERP System') }}</h4><span>Lupa Password</span></div>
<div class="card-body">
<p>Masukkan email akun Anda untuk menerima tautan reset password.</p>
@if (session('status'))<div class="alert alert-success" role="status">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('password.email') }}">
@csrf
<div class="mb-3"><label for="email" class="form-label">Email</label>
<input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<button type="submit" class="btn btn-primary w-100">Kirim Tautan Reset</button>
</form>
</div><div class="card-footer text-center"><a href="{{ route('login') }}">Kembali ke Login</a></div></div>
</div></div></main>
</body></html>
