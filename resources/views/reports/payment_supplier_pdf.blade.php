<!DOCTYPE html>
<html>
<head>
    <title>Laporan Payment ke Supplier</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'reports-payment_supplier_pdf'])
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN PAYMENT KE SUPPLIER</div>
        <div class="subtitle">Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No Invoice Supplier</th>
                <th>PO Supplier</th>
                <th>Supplier</th>
                <th>Tgl Invoice</th>
                <th>Jatuh Tempo</th>
                <th>Jumlah</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
            <tr>
                <td>{{ $inv->invoice_number ?? '-' }}</td>
                <td>{{ $inv->poSupplier->po_supplier_number ?? '-' }}</td>
                <td>{{ $inv->poSupplier->supplier->name ?? '-' }}</td>
                <td>{{ date('d/m/Y', strtotime($inv->invoice_date)) }}</td>
                <td>{{ $inv->due_date ? date('d/m/Y', strtotime($inv->due_date)) : '-' }}</td>
                <td class="text-right">Rp {{ number_format($inv->amount ?? $inv->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst($inv->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5"><strong>Total Payment</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($invoices->sum('amount') ?? $invoices->sum('total'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>