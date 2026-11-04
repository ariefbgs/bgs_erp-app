@php
    $documents = [];
    $po = $receipt->poCustomer;
    $formatDate = static fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '-';
    if ($po) {
        $documents[] = ['type' => 'PO Customer', 'number' => $po->po_number, 'date' => $formatDate($po->po_date), 'copies' => '1 Rangkap', 'note' => '-'];
    }
    foreach ($receipt->deliveryOrders as $doDocument) {
        $documents[] = ['type' => 'Delivery Order', 'number' => $doDocument->do_number, 'date' => $formatDate($doDocument->delivery_date), 'copies' => '3 Rangkap', 'note' => '-'];
    }
    foreach ($receipt->invoices as $invoiceDocument) {
        $documents[] = ['type' => 'Sales Invoice', 'number' => $invoiceDocument->invoice_number, 'date' => $formatDate($invoiceDocument->invoice_date), 'copies' => '1 Rangkap', 'note' => 'Asli'];
    }
    $taxNumbers = [];
    $missingTax = [];
    foreach ($receipt->invoices as $invoiceDocument) {
        $taxNumber = trim((string) $invoiceDocument->tax_invoice_number);
        if ($taxNumber === '') {
            $missingTax[] = $invoiceDocument->invoice_number;
            continue;
        }
        if (in_array($taxNumber, $taxNumbers, true)) continue;
        $taxNumbers[] = $taxNumber;
        $documents[] = ['type' => 'Faktur Pajak', 'number' => $taxNumber, 'date' => '-', 'copies' => '2 Rangkap', 'note' => '-'];
    }
@endphp
<table class="items-table table table-bordered bgs-document-u-0229a2b9588e">
    <thead>
    <tr class="bgs-document-u-f804fe98468a">
        <th class="bgs-document-u-b331e10fb62c">No</th>
        <th class="bgs-document-u-5045fccb9c9b">Jenis Dokumen</th>
        <th class="bgs-document-u-435c6092e71b">Nomor Dokumen</th>
        <th class="bgs-document-u-36fcb35bba2a">Tanggal</th>
        <th class="bgs-document-u-011df20c7aa8">Jumlah</th>
        <th class="bgs-document-u-43ec7c26b53e">Keterangan</th>
    </tr>
    </thead>
    <tbody>
    @foreach($documents as $document)
    <tr class="bgs-document-u-a8b886cfa25c">
        <td class="bgs-document-u-0310749fd648">{{ $loop->iteration }}</td>
        <td class="bgs-document-u-076fe228a615">{{ $document['type'] }}</td>
        <td class="bgs-document-u-f6909622c16d">{{ $document['number'] ?? '-' }}</td>
        <td class="bgs-document-u-0310749fd648">{{ $document['date'] }}</td>
        <td class="bgs-document-u-0310749fd648">{{ $document['copies'] }}</td>
        <td class="bgs-document-u-0310749fd648">{{ $document['note'] }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@if(count($missingTax))
<p class="bgs-document-u-54951062ae0b">Faktur Pajak belum tercantum untuk invoice: {{ implode(', ', $missingTax) }}. Lengkapi nomor Faktur Pajak sebelum pengiriman dokumen.</p>
@endif
