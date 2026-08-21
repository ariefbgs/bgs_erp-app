@extends('layouts.app')
@section('title', 'Create New Documentation')
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
    
    /* Header dengan gradasi warna Emerald Green */
    .card-header-custom {
        background: linear-gradient(135deg, #10b981, #047857);
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

    /* Input dan Select Custom Focus (Nuansa Hijau) */
    .form-control, .form-select {
        border-color: #cbd5e1;
        border-radius: 8px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #34d399;
        box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.15);
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
    .btn-submit-green {
        background-color: #10b981;
        border-color: #10b981;
        color: white;
    }
    .btn-submit-green:hover {
        background-color: #059669;
        border-color: #059669;
        color: white;
    }
</style>

<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
            <h4>
                <i class="bi bi-plus-circle-fill me-2"></i>
                Create New Documentation Structure
            </h4>
            <a href="{{ route('user-manuals.index') }}" class="btn btn-light-custom btn-sm px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i>
                Back to Index
            </a>
        </div>
        
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-8">
                    <form action="{{ route('user-manuals.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="module_name" class="filter-label">Target Modul ERP</label>
                            <select name="module_name" id="module_name" class="form-select py-2.5 @error('module_name') is-invalid @enderror" required>
                                <option value="" disabled selected>-- Pilih Modul ERP --</option>
                                @foreach($modules as $key => $value)
                                    <option value="{{ $key }}" {{ old('module_name') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('module_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="type" class="filter-label">Tipe Tingkatan Panduan</label>
                            <select name="type" id="type" class="form-select py-2.5 @error('type') is-invalid @enderror" required>
                                <option value="modul" {{ old('type') == 'modul' ? 'selected' : '' }}>Modul Utama (Halaman Index / Beranda Modul)</option>
                                <option value="sub_modul" {{ old('type') == 'sub_modul' ? 'selected' : '' }}>Sub-Modul (Create, Edit, Show, Delete, Print)</option>
                                <option value="feature" {{ old('type') == 'feature' ? 'selected' : '' }}>Fitur / Tombol Spesifik (Contoh: Price Calculation)</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4" id="parent_container" style="display: none;">
                            <label for="parent_id" class="filter-label text-success"><i class="bi bi-diagram-2 me-1"></i>Pilih Panduan Induk (Parent)</label>
                            <select name="parent_id" id="parent_id" class="form-select py-2.5 @error('parent_id') is-invalid @enderror">
                                <option value="">-- Pilih Dokumen Induk --</option>
                                @foreach($parentManuals as $parent)
                                    <option value="{{ $parent->id }}" data-module="{{ $parent->module_name }}" data-type="{{ $parent->type }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                        [{{ strtoupper($parent->type) }}] {{ $parent->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted mt-1">Hubungkan dokumen ini ke tingkatan di atasnya agar hirarki menu terbentuk rapi.</div>
                        </div>

                        <div class="mb-4">
                            <label for="title" class="filter-label">Judul Dokumen Panduan</label>
                            <input type="text" name="title" id="title" class="form-control py-2.5 @error('title') is-invalid @enderror" placeholder="Contoh: Cara Menggunakan Fitur Price Calculation" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="content" class="filter-label">Isi / Langkah-Langkah Panduan</label>
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror" rows="12" placeholder="Tuliskan petunjuk operasional sistem di sini secara detail..." style="border-radius: 8px;" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="text-muted my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user-manuals.index') }}" class="btn btn-outline-secondary py-2 px-4 fw-semibold" style="border-radius: 6px;">Batal</a>
                            <button type="submit" class="btn btn-submit-green py-2 px-4 fw-bold shadow-sm" style="border-radius: 6px;">
                                <i class="bi bi-save-fill me-2"></i>Simpan Panduan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="info-panel" style="border-top: 4px solid #10b981;">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-diagram-3-fill text-success me-2"></i>
                            Konsep Struktur Panduan
                        </h6>
                        <p class="small text-muted mb-3" style="line-height: 1.6;">Sistem membagi dokumentasi bantuan operasional ke dalam 3 level utama untuk mempermudah pemetaan:</p>
                        
                        <div class="mb-3 p-2.5 rounded-3" style="background-color: #f0fdf4; border-left: 3px solid #10b981;">
                            <div class="small fw-bold text-success mb-0">LEVEL 1: MODUL</div>
                            <span class="text-muted d-block" style="font-size: 0.8rem;">Penjelasan global saat user baru masuk halaman utama (Index) modul terkait.</span>
                        </div>
                        
                        <div class="mb-3 p-2.5 rounded-3" style="background-color: #fffbeb; border-left: 3px solid #f59e0b;">
                            <div class="small fw-bold text-warning mb-0">LEVEL 2: SUB-MODUL</div>
                            <span class="text-muted d-block" style="font-size: 0.8rem;">Petunjuk khusus untuk aksi pengisian form (Create, Edit) atau manajemen cetak (Print).</span>
                        </div>
                        
                        <div class="p-2.5 rounded-3" style="background-color: #f0fdf4; border-left: 3px solid #10b981;">
                            <div class="small fw-bold text-success mb-0">LEVEL 3: FITUR</div>
                            <span class="text-muted d-block" style="font-size: 0.8rem;">Logika perhitungan atau penanganan tombol khusus/rumit (Contoh: Price Calculation).</span>
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
        
        // VARIABEL KUNCI: Menyimpan ID yang sedang dipilih oleh user secara real-time
        let activeParentId = parentSelect.value || "{{ old('parent_id') }}";

        // Pantau jika user mengubah pilihan Dokumen Induk secara manual
        parentSelect.addEventListener('change', function() {
            activeParentId = this.value;
        });
        
        // Cadangkan element option asli ke dalam array permanen saat halaman pertama dimuat
        const originalOptions = [];
        for (let i = 0; i < parentSelect.options.length; i++) {
            if (parentSelect.options[i].value !== "") {
                originalOptions.push(parentSelect.options[i].cloneNode(true));
            }
        }

        function filterParents() {
            const selectedModule = moduleSelect.value;
            const selectedType = typeSelect.value;

            // Jika tipenya 'modul' (paling atas), sembunyikan induk
            if (selectedType === 'modul' || !selectedModule) {
                parentContainer.style.display = 'none';
                parentSelect.value = '';
                activeParentId = ''; // Reset memori pilihan
                parentSelect.removeAttribute('required');
                return;
            }

            // Tampilkan container induk dan wajibkan pengisian
            parentContainer.style.display = 'block';
            parentSelect.setAttribute('required', 'required');

            // Kosongkan dropdown, sisakan placeholder default
            parentSelect.innerHTML = '<option value="">-- Pilih Dokumen Induk --</option>';

            // Mulai memfilter dari cadangan data asli kita
            originalOptions.forEach(option => {
                const optionModule = option.getAttribute('data-module');
                const optionType = option.getAttribute('data-type');

                let isMatch = false;
                if (optionModule === selectedModule) {
                    if (selectedType === 'sub_modul' && optionType === 'modul') {
                        isMatch = true;
                    } else if (selectedType === 'feature' && optionType === 'sub_modul') {
                        isMatch = true;
                    }
                }

                if (isMatch) {
                    const clonedOption = option.cloneNode(true);
                    
                    // KUNCI AMAN: Jika id cocok dengan yang sedang aktif dipilih, pertahankan status 'selected'
                    if (clonedOption.value === activeParentId) {
                        clonedOption.setAttribute('selected', 'selected');
                    } else {
                        clonedOption.removeAttribute('selected');
                    }
                    
                    parentSelect.appendChild(clonedOption);
                }
            });
        }

        // Jalankan filter hanya ketika Modul Utama atau Tipe Tingkatan berubah
        moduleSelect.addEventListener('change', filterParents);
        typeSelect.addEventListener('change', filterParents);
        
        // Jalankan filter pertama kali saat halaman dimuat
        filterParents();
    });
</script>
@endpush