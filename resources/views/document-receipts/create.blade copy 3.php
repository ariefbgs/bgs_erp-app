@extends('layouts.app')
@section('title', 'Create Document Receipt')
@section('content')

{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container .select2-selection--multiple {
        min-height: 40px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 4px 12px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd !important;
        color: #fff !important;
        border: none !important;
        border-radius: 4px !important;
        padding: 2px 8px !important;
        margin: 2px 4px 2px 0 !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff !important;
        margin-right: 4px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffc107 !important;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Create Document Receipt</h4>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <strong>Terjadi kesalahan!</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form id="receiptForm" action="{{ route('document-receipts.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Pilih Delivery Order -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Select Delivery Order <span class="text-danger">*</span></label>
                    <select name="delivery_order_id" id="delivery_order_id" class="form-control select2" data-placeholder="-- Select Delivery Order --" required>
                        <option value="">-- Select Delivery Order --</option>
                        @foreach($deliveryOrders as $do)
                            <option value="{{ $do->id }}" {{ old('delivery_order_id') == $do->id ? 'selected' : '' }}>
                                {{ $do->do_number }} - {{ $do->poCustomer->customer->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Receipt Date <span class="text-danger">*</span></label>
                    <input type="date" name="receipt_date" class="form-control" value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                </div>
            </div>

            <!-- Informasi PO Customer (akan diisi via AJAX) -->
            <div id="poInfoContainer" style="display: none;">
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <strong>PO Customer Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="35%">No. PO</th><td id="info_po_number">-</td></tr>
                                    <tr><th>Customer</th><td id="info_customer_name">-</td></tr>
                                    <tr><th>Address</th><td id="info_customer_address">-</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
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
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Invoice Number(s) <span class="text-danger">*</span></label>
                        <select name="invoice_number[]" id="invoice_number" class="form-control select2-invoice" multiple="multiple" data-placeholder="-- Select Invoice(s) --" required>
                            <option value="">-- Select Invoice --</option>
                        </select>
                        <small class="text-muted">Anda dapat memilih lebih dari satu invoice.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax Invoice Number</label>
                        <input type="text" name="tax_invoice_number" id="tax_invoice_number" class="form-control" readonly placeholder="Akan terisi otomatis dari invoice yang dipilih">
                        <small class="text-muted">Tax Invoice Number akan digabung dengan koma jika lebih dari satu invoice.</small>
                    </div>
                </div>
            </div>

            <!-- Additional fields (hidden until DO selected) -->
            <div id="additionalFields" style="display: none;">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">PO Number</label>
                        <input type="text" id="display_po_number" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">DO Number</label>
                        <input type="text" id="display_do_number" class="form-control" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Attachment (PDF/Image)</label>
                    <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    <small class="text-muted">Maximum 5MB. Images will be compressed.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-save-fill"></i> Save Document Receipt
                    </button>
                    <a href="{{ route('document-receipts.index') }}" class="btn btn-secondary">Cancel</a>
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
        // Select2 untuk Delivery Order
        $('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
            $(this).select2({
                placeholder: $(this).data('placeholder') || 'Select Option',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: 0
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
                minimumResultsForSearch: 0
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
    // EVENT: INVOICE CHANGE -> AUTO FILL TAX NUMBER (gabung semua)
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