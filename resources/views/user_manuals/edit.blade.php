@extends('layouts.app')
@section('title', 'Edit Documentation Structure')
@section('content')
<style>
    /* Mengubah Background dasar halaman agar senada */
    body {
        background-color: #f1f5f9;
    }

    /* Desain Card Utama */
    .main-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        background: #fff;
        overflow: hidden;
    }
    
    /* Header dengan gradasi warna Sunset Orange / Amber */
    .card-header-custom {
        background: linear-gradient(135deg, #f97316, #ea580c);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    .card-header-custom h4 {
        color: white;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* Form Label Custom Styling */
    .filter-label {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569; 
        font-weight: 700;
        margin-bottom: 6px;
    }

    /* Input dan Select Custom Focus (Nuansa Orange) */
    .form-control, .form-select {
        border-color: #cbd5e1;
        border-radius: 8px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #fb923c;
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.15);
    }

    /* Sidebar Info Panel */
    .info-panel {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
    }

    /* Tombol Header & Custom Action */
    .btn-light-custom {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        font-weight: 600;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .btn-light-custom:hover {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }
    .btn-submit-orange {
        background-color: #f97316;
        border-color: #f97316;
        color: white;
    }
    .btn-submit-orange:hover {
        background-color: #d97706;
        border-color: #d97706;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4>
                <i class="bi bi-pencil-square me-2"></i>
                Modify Documentation Structure
            </h4>
            <a href="{{ route('user-manuals.index') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i>
                Back to Index
            </a>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <form action="{{ route('user-manuals.update', $userManual->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="module_name" class="filter-label">Target Modul ERP</label>
                            <select name="module_name" id="module_name" class="form-select py-2.5 @error('module_name') is-invalid @enderror" required>
                                @foreach($modules as $key => $value)
                                    <option value="{{ $key }}" {{ old('module_name', $userManual->module_name) == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('module_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="type" class="filter-label">Tipe Tingkatan Panduan</label>
                            <select name="type" id="type" class="form-select py-2.5 @error('type') is-invalid @enderror" required>
                                <option value="modul" {{ old('type', $userManual->type) == 'modul' ? 'selected' : '' }}>Modul Utama (Halaman Index / Beranda Modul)</option>
                                <option value="sub_modul" {{ old('type', $userManual->type) == 'sub_modul' ? 'selected' : '' }}>Sub-Modul (Create, Edit, Show, Delete, Print)</option>
                                <option value="feature" {{ old('type', $userManual->type) == 'feature' ? 'selected' : '' }}>Fitur / Tombol Spesifik (Contoh: Price Calculation)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4" id="parent_container" style="display: none;">
                            <label for="parent_id" class="filter-label text-warning"><i class="bi bi-diagram-2 me-1"></i>Pilih Panduan Induk (Parent)</label>
                            <select name="parent_id" id="parent_id" class="form-select py-2.5 @error('parent_id') is-invalid @enderror">
                                <option value="">-- Pilih Dokumen Induk --</option>
                                @foreach($parentManuals as $parent)
                                    {{-- Proteksi rekursif: Jangan tampilkan data diri sendiri --}}
                                    @if($parent->id !== $userManual->id)
                                        <option value="{{ $parent->id }}" 
                                                data-module="{{ $parent->module_name }}" 
                                                data-type="{{ $parent->type }}" 
                                                {{ old('parent_id', $userManual->parent_id) == $parent->id ? 'selected' : '' }}>
                                            [{{ strtoupper($parent->type) }}] {{ $parent->title }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="title" class="filter-label">Judul Dokumen Panduan</label>
                            <input type="text" name="title" id="title" class="form-control py-2.5 @error('title') is-invalid @enderror" value="{{ old('title', $userManual->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="content" class="filter-label">Isi / Langkah-Langkah Panduan</label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="12" style="border-radius: 8px;" required>{{ old('content', $userManual->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="text-muted my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user-manuals.index') }}" class="btn btn-outline-secondary py-2 px-4 fw-semibold" style="border-radius: 6px;">Batal</a>
                            <button type="submit" class="btn btn-submit-orange py-2 px-4 fw-bold shadow-sm" style="border-radius: 6px;">
                                <i class="bi bi-arrow-repeat me-2"></i>Perbarui Panduan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="info-panel" style="border-top: 4px solid #f97316;">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-clock-history text-warning me-2"></i>
                            Informasi Log Data
                        </h6>
                        
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless small text-muted mb-0" style="line-height: 1.8;">
                                <tr>
                                    <td class="ps-0" style="width: 120px; font-weight: 600;">Dibuat Pada</td>
                                    <td>: {{ $userManual->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-0" style="font-weight: 600;">Terakhir Diperbarui</td>
                                    <td>: {{ $userManual->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-0" style="font-weight: 600;">ID Dokumen</td>
                                    <td>: <span class="font-monospace text-secondary">#{{ $userManual->id }}</span></td>
                                </tr>
                            </table>
                        </div>
                        
                        <hr class="text-muted my-3">
                        <div class="p-2.5 rounded-3 bg-light" style="font-size: 0.78rem; line-height: 1.5;">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                            <strong>Pemberitahuan Keamanan:</strong> Mengubah relasi level hirarki di sini akan memengaruhi pemetaan tombol bantuan **"?"** pada modul transaksi operasional secara langsung.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const moduleSelect = document.getElementById('module_name');
        const typeSelect = document.getElementById('type');
        const parentContainer = document.getElementById('parent_container');
        const parentSelect = document.getElementById('parent_id');
        
        // Simpan semua opsi asli saat halaman dimuat pertama kali
        const originalOptions = Array.from(parentSelect.options).filter(opt => opt.value !== "");
        const currentSavedParentId = "{{ old('parent_id', $userManual->parent_id) }}";

        function filterParents() {
            const selectedModule = moduleSelect.value;
            const selectedType = typeSelect.value;

            // JIKA TIPE ADALAH MODUL (TINGKAT TERATAS)
            if (selectedType === 'modul' || !selectedModule) {
                parentContainer.style.display = 'none';
                parentSelect.value = '';
                parentSelect.removeAttribute('required'); // <-- PENTING: Menghapus required di form edit
                return;
            }

            // JIKA TIPE ADALAH SUB-MODUL ATAU FITUR
            parentContainer.style.display = 'block';
            parentSelect.setAttribute('required', 'required'); // <-- Memasang kembali required

            // Reset dan bangun ulang isi select dropdown
            parentSelect.innerHTML = '<option value="">-- Pilih Dokumen Induk --</option>';

            originalOptions.forEach(option => {
                const optionModule = option.getAttribute('data-module');
                const optionType = option.getAttribute('data-type');

                // Aturan penyaringan bertingkat (Cascading Filter)
                let isMatch = false;
                if (optionModule === selectedModule) {
                    if (selectedType === 'sub_modul' && optionType === 'modul') {
                        isMatch = true;
                    } else if (selectedType === 'feature' && optionType === 'sub_modul') {
                        isMatch = true;
                    }
                }

                if (isMatch) {
                    // Setel status terpilih (selected) jika id cocok dengan data lama/database
                    if (option.value === currentSavedParentId) {
                        option.setAttribute('selected', 'selected');
                    } else {
                        option.removeAttribute('selected');
                    }
                    parentSelect.appendChild(option);
                }
            });
        }

        moduleSelect.addEventListener('change', filterParents);
        typeSelect.addEventListener('change', filterParents);
        
        // Jalankan filter saat edit pertama kali terbuka agar sinkron dengan data database
        filterParents();
    });
</script>
@endpush