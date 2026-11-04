@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-user-access-index bgs-mode-index')
@section('content')
<div class="container-fluid">
<div class="bgs-page-header rounded mb-3"><h2>Pengaturan Akses Pengguna</h2></div>
<p>Kelola role pengguna, akses fitur, dan halaman setelah login.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@foreach($users as $user)
<form method="POST" action="{{ route('user-access.update', $user) }}" class="card card-body mb-3">
@csrf @method('PUT')
<h5>{{ $user->name }} — {{ $user->email }}</h5>
<div class="row g-2">
<div class="col-md-3"><label class="form-label">Role</label><select name="role" class="form-select">
<option value="admin" @selected($user->role === 'admin')>Admin</option>
<option value="staff" @selected($user->role !== 'admin')>User</option>
</select></div>
<div class="col-md-3"><label class="form-label">Akses fitur lengkap</label><select name="full_features" class="form-select">
<option value="1" @selected($user->canUseAdminFeatures())>Ya</option>
<option value="0" @selected(!$user->canUseAdminFeatures())>Tidak</option>
</select></div>
<div class="col-md-3"><label class="form-label">Akses Dashboard</label><select name="dashboard_access" class="form-select">
<option value="1" @selected($user->canAccessDashboard())>Ya — masuk Dashboard</option>
<option value="0" @selected(!$user->canAccessDashboard())>Tidak — masuk Welcome</option>
</select></div>
<div class="col-md-3 align-self-end"><button class="btn btn-primary">Simpan Pengaturan</button></div>
</div>
</form>
@endforeach
</div>
@endsection
