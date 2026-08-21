<!DOCTYPE html>
<html>
<head>
    <title>Laporan Delivery Order</title>
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