<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN PENJUALAN</div>
        <div class="subtitle">Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}</div>
    </div>

    <h4>A. PO Customer</h4>
    <table>
        <thead>
            <tr><th>No PO</th><th>Customer</th><th>Tgl PO</th><th>Delivery Date</th><th>Total</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($poCustomers as $po)
            <tr>
                <td>{{ $po->po_number }}</td>
                <td>{{ $po->customer->name }}</td>
                <td>{{ date('d/m/Y', strtotime($po->po_date)) }}</td>
                <td>{{ $po->delivery_date ? date('d/m/Y', strtotime($po->delivery_date)) : '-' }}</td>
                <td class="text-right">Rp {{ number_format($po->total, 0, ',', '.') }}</td>
                <td>{{ ucfirst($po->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>Total PO Customer</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($poCustomers->sum('total'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <h4>B. Delivery Order</h4>
    <table>
        <thead>
            <tr><th>No DO</th><th>PO Customer</th><th>Customer</th><th>Tgl Kirim</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($deliveryOrders as $do)
            <tr>
                <td>{{ $do->do_number }}</td>
                <td>{{ $do->poCustomer->po_number ?? '-' }}</td>
                <td>{{ $do->poCustomer->customer->name ?? '-' }}</td>
                <td>{{ date('d/m/Y', strtotime($do->delivery_date)) }}</td>
                <td>{{ ucfirst($do->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h4>C. Sales Invoice</h4>
    <table>
        <thead>
            <tr><th>No Invoice</th><th>Tipe</th><th>Customer</th><th>Tgl Invoice</th><th>Total</th><th>DP</th><th>Sisa</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
            <tr>
                <td>{{ $inv->invoice_number }}</td>
                <td>{{ ucfirst($inv->type) }}</td>
                <td>{{ $inv->poCustomer->customer->name ?? '-' }}</td>
                <td>{{ date('d/m/Y', strtotime($inv->invoice_date)) }}</td>
                <td class="text-right">Rp {{ number_format($inv->total, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($inv->dp_amount, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</td>
                <td>{{ ucfirst($inv->status) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>Total Invoice</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($invoices->sum('total'), 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($invoices->sum('dp_amount'), 0, ',', '.') }}</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($invoices->sum('remaining_amount'), 0, ',', '.') }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>