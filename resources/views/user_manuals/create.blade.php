@extends('layouts.app')
@section('bgs-page-class', 'bgs-page-user-manuals-create bgs-mode-create')
@section('title', 'Create New Documentation')
@section('content')


<div class="container-fluid py-4">
    <div class="card main-card">
        <div class="card-header-custom d-flex justify-content-between align-items-center bgs-page-header">
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

                        <div class="mb-4 bgs-u-6e22c58a7a6a" id="parent_container">
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
                            <textarea name="content" id="content" class="form-control @error('content') is-invalid @enderror bgs-u-25561090436f" rows="12" placeholder="Tuliskan petunjuk operasional sistem di sini secara detail..." required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="text-muted my-4">

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('user-manuals.index') }}" class="btn btn-outline-secondary py-2 px-4 fw-semibold bgs-u-8dbba0d7e895">Batal</a>
                            <button type="submit" class="btn btn-submit-green py-2 px-4 fw-bold shadow-sm bgs-u-8dbba0d7e895">
                                <i class="bi bi-save-fill me-2"></i>Simpan Panduan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-lg-4">
                    <div class="info-panel bgs-u-f084f7a04044">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-diagram-3-fill text-success me-2"></i>
                            Konsep Struktur Panduan
                        </h6>
                        <p class="small text-muted mb-3 bgs-u-9477dd6c397e">Sistem membagi dokumentasi bantuan operasional ke dalam 3 level utama untuk mempermudah pemetaan:</p>
                        
                        <div class="mb-3 p-2.5 rounded-3 bgs-u-382ab01da681">
                            <div class="small fw-bold text-success mb-0">LEVEL 1: MODUL</div>
                            <span class="text-muted d-block bgs-u-aae30f4ce161">Penjelasan global saat user baru masuk halaman utama (Index) modul terkait.</span>
                        </div>
                        
                        <div class="mb-3 p-2.5 rounded-3 bgs-u-bdf2e2ae4b6e">
                            <div class="small fw-bold text-warning mb-0">LEVEL 2: SUB-MODUL</div>
                            <span class="text-muted d-block bgs-u-aae30f4ce161">Petunjuk khusus untuk aksi pengisian form (Create, Edit) atau manajemen cetak (Print).</span>
                        </div>
                        
                        <div class="p-2.5 rounded-3 bgs-u-382ab01da681">
                            <div class="small fw-bold text-success mb-0">LEVEL 3: FITUR</div>
                            <span class="text-muted d-block bgs-u-aae30f4ce161">Logika perhitungan atau penanganan tombol khusus/rumit (Contoh: Price Calculation).</span>
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