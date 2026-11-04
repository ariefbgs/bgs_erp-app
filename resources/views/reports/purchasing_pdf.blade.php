<!DOCTYPE html>
<html>
<head>
    <title>Laporan Pembelian</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'reports-purchasing_pdf'])
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN PEMBELIAN</div>
        <div class="subtitle">Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}</div>
    </div>

    <h4>A. PO Supplier</h4>
    <table>
        <thead>
            <tr><th>No PO Supplier</th><th>Supplier</th><th>Tgl PO</th><th>Expected Date</th><th>Total</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($poSuppliers as $po)
            <tr>
                <td>{{ $po->po_supplier_number }}</td>
                <td>{{ $po->supplier->name }}</td>
                <td>{{ date('d/m/Y', strtotime($po->po_date)) }}</td>
                <td>{{ $po->expected_date ? date('d/m/Y', strtotime($po->expected_date)) : '-' }}</td>
                <td class="text-right">Rp {{ number_format($po->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst($po->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>Total PO Supplier</strong></td>
                <td><strong>Rp {{ number_format($poSuppliers->sum('total'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <h4>B. Penerimaan Barang (Goods Receipt)</h4>
    <table>
        <thead>
            <tr><th>No Receipt</th><th>PO Supplier</th><th>Supplier</th><th>Tgl Terima</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($goodsReceipts as $gr)
            <tr>
                <td>{{ $gr->receipt_number }}</td>
                <td>{{ $gr->poSupplier->po_supplier_number ?? '-' }}</td>
                <td>{{ $gr->poSupplier->supplier->name ?? '-' }}</td>
                <td>{{ date('d/m/Y', strtotime($gr->receipt_date)) }}</td>
                <td>{{ ucfirst($gr->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>