@extends('layouts.app')
@section('title', 'Create Document Receipt')
@section('content')

{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* ========================================================== */
    /* STYLING SELECT2 UMUM */
    /* ========================================================== */
    .select2-container .select2-selection {
        height: auto !important;
        min-height: 40px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        display: flex;
        align-items: center;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
        background: #fff !important;
    }

    .select2-container .select2-selection:hover {
        border-color: #86b7fe !important;
    }

    .select2-container--open .select2-selection {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        max-width: calc(100vw - 24px) !important; /* memberi ruang di sisi */
        left: 0 !important;
        right: 0 !important;
    }

    /* Single Select */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #212529 !important;
        line-height: 1.5 !important;
        padding: 8px 12px !important;
        width: 100%;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px !important;
        right: 8px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #6c757d transparent transparent transparent !important;
    }

    /* Multiple Select */
    .select2-container--default .select2-selection--multiple {
        min-height: 40px !important;
        padding: 4px 8px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 0 !important;
        margin: 0 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 4px !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: #0d6efd !important;
        border: none !important;
        border-radius: 4px !important;
        color: #fff !important;
        padding: 2px 8px 2px 6px !important;
        margin: 0 !important;
        font-size: 0.85rem !important;
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
        animation: fadeIn 0.2s ease !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.8) !important;
        border: none !important;
        background: transparent !important;
        padding: 0 2px !important;
        font-size: 14px !important;
        line-height: 1 !important;
        cursor: pointer !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffc107 !important;
        background: transparent !important;
    }

    .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
        color: #6c757d !important;
        font-size: 0.9rem !important;
        padding: 4px 0 !important;
    }

    /* Input Search di dalam Select2 */
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        padding: 8px 12px !important;
        font-size: 0.9rem !important;
        min-height: 38px !important;
        width: 100% !important;
    }

    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #86b7fe !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        outline: none !important;
    }

    /* Dropdown Options */
    .select2-container--default .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.9rem !important;
        white-space: normal !important;
        word-break: break-word !important;
    }

    .select2-container--default .select2-results__option--highlighted {
        background-color: #0d6efd !important;
        color: #fff !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #e9ecef !important;
        color: #212529 !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected="true"] {
        background-color: #0d6efd !important;
        color: #fff !important;
    }

    /* Dropdown Container */
    .select2-dropdown {
        width: 100% !important;
        max-width: calc(100vw - 24px) !important; /* Beri ruang 12px kiri & kanan */
        width: auto !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        margin-top: 4px !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        box-sizing: border-box !important;
        left: 0 !important;
    }

    /* Animasi */
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Wrapper untuk label dan select */
    .form-group-select {
        margin-bottom: 1rem;
    }

    .form-group-select .form-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #334155;
        margin-bottom: 4px;
        display: block;
    }

    .form-group-select .text-muted {
        font-size: 0.75rem;
        margin-top: 4px;
    }

    /* Badge counter untuk multiple select */
    .select2-selection__choice__count {
        display: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .select2-container .select2-selection {
            min-height: 38px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
        }
    }
</style>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0"><i class="bi bi-file-earmark-plus-fill me-2"></i> Create Document Receipt</h4>
    </div>

    <div class="card-body p-4">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-left: 4px solid #dc2626 !important;">
                <div class="d-flex">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>
                        <strong>Terjadi kesalahan!</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-left: 4px solid #dc2626 !important;">
                <div class="d-flex">
                    <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form id="receiptForm" action="{{ route('document-receipts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Pilih Delivery Order -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="form-group-select">
                        <label class="form-label">
                            <i class="bi bi-truck text-primary me-1"></i> Select Delivery Order <span class="text-danger">*</span>
                        </label>
                        <select name="delivery_order_id" id="delivery_order_id" class="form-control select2" data-placeholder="-- Select Delivery Order --" required>
                            <option value="">-- Select Delivery Order --</option>
                            @foreach($deliveryOrders as $do)
                                <option value="{{ $do->id }}" {{ old('delivery_order_id') == $do->id ? 'selected' : '' }}>
                                    {{ $do->do_number }} - {{ $do->poCustomer->customer->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group-select">
                        <label class="form-label">
                            <i class="bi bi-calendar3 text-primary me-1"></i> Receipt Date <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="receipt_date" class="form-control" value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                    </div>
                </div>
            </div>

            <!-- Informasi PO Customer (akan diisi via AJAX) -->
            <div id="poInfoContainer" style="display: none;">
                <div class="card mb-4 border-0 shadow-sm" style="background: #f8fafc;">
                    <div class="card-header bg-info text-white py-2">
                        <strong><i class="bi bi-info-circle-fill me-1"></i> PO Customer Information</strong>
                    </div>
                    <div class="card-body py-3">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th width="35%">No. PO</th><td id="info_po_number">-</td></tr>
                                    <tr><th>Customer</th><td id="info_customer_name">-</td></tr>
                                    <tr><th>Address</th><td id="info_customer_address">-</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless mb-0">
                                    <tr><th>DO Number</th><td id="info_do_number">-</td></tr>
                                    <tr><th>Shipping Address</th><td id="info_shipping_address">-</td></tr>
                                    <tr><th>Receiver Name</th><td id="info_receiver_name">-</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pilihan Invoice Number (Multi-Select) -->
            <div id="invoiceSelector" style="display: none;">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-group-select">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-text text-primary me-1"></i> Invoice Number(s) <span class="text-danger">*</span>
                            </label>
                            <select name="invoice_number[]" id="invoice_number" class="form-control select2-invoice" multiple="multiple" data-placeholder="-- Select Invoice(s) --" required>
                                <option value="">-- Select Invoice --</option>
                            </select>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-info-circle"></i> Anda dapat memilih lebih dari satu invoice. 
                                <span id="selected_invoice_count" class="fw-semibold text-primary">(0 dipilih)</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group-select">
                            <label class="form-label">
                                <i class="bi bi-receipt text-primary me-1"></i> Tax Invoice Number
                            </label>
                            <input type="text" name="tax_invoice_number" id="tax_invoice_number" class="form-control bg-light" readonly placeholder="Akan terisi otomatis dari invoice yang dipilih">
                            <div class="text-muted small mt-1">
                                <i class="bi bi-info-circle"></i> Tax Invoice Number akan digabung dengan koma jika lebih dari satu invoice.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional fields (hidden until DO selected) -->
            <div id="additionalFields" style="display: none;">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-group-select">
                            <label class="form-label"><i class="bi bi-file-earmark-text text-secondary me-1"></i> PO Number</label>
                            <input type="text" id="display_po_number" class="form-control bg-light" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group-select">
                            <label class="form-label"><i class="bi bi-truck text-secondary me-1"></i> DO Number</label>
                            <input type="text" id="display_do_number" class="form-control bg-light" readonly>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-paperclip text-secondary me-1"></i> Attachment (PDF/Image)</label>
                    <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <div class="text-muted small mt-1">Maximum 5MB. Images will be compressed.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-chat-left-text text-secondary me-1"></i> Remarks</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan...">{{ old('notes') }}</textarea>
                </div>

                <div class="text-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                        <i class="bi bi-save-fill me-1"></i> Save Document Receipt
                    </button>
                    <a href="{{ route('document-receipts.index') }}" class="btn btn-secondary px-4 py-2">
                        <i class="bi bi-x-circle-fill me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- jQuery & Select2 --}}
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // =============================================================
    // INISIALISASI SELECT2
    // =============================================================
    function initSelect2() {
        // Select2 untuk Delivery Order (single select)
        $('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select Option',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0,
                dropdownAutoWidth: true,
                theme: 'default'
            });
        });

        // Select2 untuk Invoice (multi-select)
        $('.select2-invoice').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select Invoice(s)',
                allowClear: true,
                width: '100%',
                multiple: true,
                minimumResultsForSearch: 0,
                dropdownAutoWidth: true,
                theme: 'default',
                closeOnSelect: false
            });
        });
    }

    // =============================================================
    // AUTOFOCUS KE SEARCH SAAT SELECT2 DIBUKA
    // =============================================================
    $(document).on('select2:open', function(e) {
        setTimeout(function() {
            var searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField) {
                searchField.focus();
            }
        }, 50);
    });

    // =============================================================
    // UPDATE COUNTER INVOICE YANG DIPILIH
    // =============================================================
    function updateInvoiceCount() {
        var selected = $('#invoice_number').val() || [];
        var count = selected.length;
        $('#selected_invoice_count').text('(' + count + ' dipilih)');
    }

    // Panggil inisialisasi
    initSelect2();

    // =============================================================
    // EVENT: DELIVERY ORDER CHANGE
    // =============================================================
    $('#delivery_order_id').on('change', function() {
        var doId = $(this).val();
        if (!doId) {
            $('#poInfoContainer').hide();
            $('#invoiceSelector').hide();
            $('#additionalFields').hide();
            $('#invoice_number').html('<option value="">-- Select Invoice --</option>');
            $('#tax_invoice_number').val('');
            $('#display_po_number').val('');
            $('#display_do_number').val('');
            $('#selected_invoice_count').text('(0 dipilih)');
            initSelect2();
            return;
        }

        // Tampilkan loading
        $('#poInfoContainer').hide();
        $('#invoiceSelector').hide();
        $('#additionalFields').hide();

        // Ambil detail DO
        $.ajax({
            url: '{{ route("document-receipts.get-do-details", "") }}/' + doId,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                // Tampilkan info PO dan DO
                $('#info_po_number').text(res.po_number || '-');
                $('#info_customer_name').text(res.customer_name || '-');
                $('#info_customer_address').text(res.customer_address || '-');
                $('#info_do_number').text(res.do_number || '-');
                $('#info_shipping_address').text(res.shipping_address || '-');
                $('#info_receiver_name').text(res.receiver_name || '-');
                $('#poInfoContainer').show();

                // Isi display fields
                $('#display_po_number').val(res.po_number || '-');
                $('#display_do_number').val(res.do_number || '-');

                // Load daftar invoice berdasarkan po_customer_id
                $.ajax({
                    url: '{{ route("document-receipts.get-invoices-by-po", "") }}/' + res.po_customer_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(invoices) {
                        // Hancurkan Select2 invoice jika ada
                        var $invoiceSelect = $('#invoice_number');
                        if ($invoiceSelect.data('select2')) {
                            $invoiceSelect.select2('destroy');
                        }

                        var options = '';
                        if (invoices && invoices.length > 0) {
                            $.each(invoices, function(i, inv) {
                                options += '<option value="' + inv.invoice_number + '" data-tax="' + (inv.tax_invoice_number || '') + '">' + inv.invoice_number + ' (' + inv.type + ')</option>';
                            });
                        } else {
                            options = '<option value="">No invoice available</option>';
                        }
                        $invoiceSelect.html(options);
                        $('#invoiceSelector').show();
                        $('#additionalFields').show();

                        // Inisialisasi ulang Select2 untuk invoice
                        initSelect2();

                        // Jika hanya ada satu invoice, langsung pilih
                        if (invoices && invoices.length === 1) {
                            $invoiceSelect.val([invoices[0].invoice_number]).trigger('change');
                        }

                        // Restore old value jika ada
                        var oldInvoices = @json(old('invoice_number'));
                        if (oldInvoices && Array.isArray(oldInvoices) && oldInvoices.length > 0) {
                            $invoiceSelect.val(oldInvoices).trigger('change');
                        }

                        updateInvoiceCount();
                    },
                    error: function(xhr) {
                        console.error('Error loading invoices:', xhr.responseText);
                        var $invoiceSelect = $('#invoice_number');
                        if ($invoiceSelect.data('select2')) {
                            $invoiceSelect.select2('destroy');
                        }
                        $invoiceSelect.html('<option value="">Error loading invoices</option>');
                        initSelect2();
                    }
                });
            },
            error: function(xhr) {
                console.error('Error loading DO details:', xhr.responseText);
                alert('Failed to load Delivery Order details. Please try again.');
                $('#poInfoContainer').hide();
                $('#invoiceSelector').hide();
                $('#additionalFields').hide();
                var $invoiceSelect = $('#invoice_number');
                if ($invoiceSelect.data('select2')) {
                    $invoiceSelect.select2('destroy');
                }
                $invoiceSelect.html('<option value="">-- Select Invoice --</option>');
                $('#tax_invoice_number').val('');
                initSelect2();
            }
        });
    });

    // =============================================================
    // EVENT: INVOICE CHANGE -> AUTO FILL TAX NUMBER & UPDATE COUNTER
    // =============================================================
    $(document).on('change', '#invoice_number', function() {
        var selected = $(this).val();
        var taxNumbers = [];
        if (selected && selected.length > 0) {
            // Loop semua invoice yang dipilih
            $.each(selected, function(index, invoiceNumber) {
                var tax = $('#invoice_number option[value="' + invoiceNumber + '"]').data('tax');
                if (tax) {
                    taxNumbers.push(tax);
                }
            });
        }
        $('#tax_invoice_number').val(taxNumbers.join(', '));
        updateInvoiceCount();
    });

    // =============================================================
    // SUBMIT FORM HANDLER (Loading)
    // =============================================================
    $('#receiptForm').on('submit', function() {
        var submitBtn = $(this).find('#submitBtn');
        submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        submitBtn.prop('disabled', true);
        return true;
    });

    // =============================================================
    // RESTORE OLD VALUE (jika validasi gagal)
    // =============================================================
    var oldDoId = "{{ old('delivery_order_id') }}";
    if (oldDoId) {
        $('#delivery_order_id').val(oldDoId).trigger('change');
    }

    var oldNotes = "{{ old('notes') }}";
    if (oldNotes) {
        $('textarea[name="notes"]').val(oldNotes);
    }
});
</script>
@endsection