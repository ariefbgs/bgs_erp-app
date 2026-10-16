<!DOCTYPE html>
<html>
<head>
    <title>Laporan Delivery Order</title>
    @include('layouts.print-styles', ['bgsPrintProfile' => 'reports-delivery_pdf'])
</head>
<body>
    <div class="header">
        <div class="title">LAPORAN DELIVERY ORDER</div>
        <div class="subtitle">Periode: {{ date('d/m/Y', strtotime($startDate)) }} - {{ date('d/m/Y', strtotime($endDate)) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>No DO</th>
                <th>PO Customer</th>
                <th>Customer</th>
                <th>Tgl Kirim</th>
                <th>Alamat Kirim</th>
                <th>Penerima</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveries as $do)
            <tr>
                <td>{{ $do->do_number }}</td>
                <td>{{ $do->poCustomer->po_number ?? '-' }}</td>
                <td>{{ $do->poCustomer->customer->name ?? '-' }}</td>
                <td>{{ date('d/m/Y', strtotime($do->delivery_date)) }}</td>
                <td>{{ $do->shipping_address ?? '-' }}</td>
                <td>{{ $do->receiver_name ?? '-' }}</td>
                <td>{{ ucfirst($do->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>