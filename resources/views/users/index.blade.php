@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark"><i class="fas fa-user-shield me-2"></i> User Management</h2>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-user-plus me-1"></i> Tambah User Baru
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" style="width: 80px;">No</th>
                            <th>Nama Pengguna</th>
                            <th>Email (Username)</th>
                            <th>Role / Status</th>
                            <th class="text-center" style="width: 250px;">Aksi / Maintain</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $index + 1 }}</td>
                            <td><span class="fw-semibold">{{ $user->name }}</span></td>
                            <td><code>{{ $user->email }}</code></td>
                            <td>
                                @if($user->email == 'arief@gmail.com')
                                    <span class="badge bg-danger">Super Admin</span>
                                @elseif($user->email == 'chicha@gmail.com')
                                    <span class="badge bg-warning text-dark">Admin</span>
                                @else
                                    <span class="badge bg-secondary">User Operasional</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#passwordModal{{ $user->id }}" title="Maintain Password">
                                        <i class="fas fa-key"></i> Password
                                    </button>

                                    @if($user->email != 'arief@gmail.com' && $user->id != auth()->id())
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $user->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i> Hapus
                                            </button>
                                        </form>
                                    @else
                                        <button class="btn btn-sm btn-light text-muted" disabled><i class="fas fa-ban"></i> Sistem</button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="passwordModal{{ $user->id }}" isset-tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-dark text-white">
                                        <h5 class="modal-title"><i class="fas fa-key me-1"></i> Reset Password: {{ $user->name }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('users.password.update', $user->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Password Baru</label>
                                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
                                                <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-dark">Update Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-1"></i> Daftarkan User Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Mita Permata" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alamat Email (Username)</label>
                        <input type="email" name="email" class="form-control" placeholder="Contoh: mita@gmail.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Utama</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection