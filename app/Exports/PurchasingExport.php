<?php

namespace App\Exports;

use App\Models\PoSupplier;
use App\Models\GoodsReceipt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PurchasingExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $startDate, $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $poSuppliers = PoSupplier::with('supplier')
            ->whereBetween('po_date', [$this->startDate, $this->endDate])
            ->get();

        $goodsReceipts = GoodsReceipt::with('poSupplier.supplier')
            ->whereBetween('receipt_date', [$this->startDate, $this->endDate])
            ->get();

        // Gabungkan dengan menambahkan tipe
        $data = [];
        foreach ($poSuppliers as $po) {
            $data[] = (object) [
                'type' => 'PO Supplier',
                'number' => $po->po_supplier_number,
                'supplier' => $po->supplier->name,
                'date' => $po->po_date,
                'total' => $po->total,
                'status' => $po->status,
            ];
        }
        foreach ($goodsReceipts as $gr) {
            $data[] = (object) [
                'type' => 'Goods Receipt',
                'number' => $gr->receipt_number,
                'supplier' => $gr->poSupplier->supplier->name ?? '-',
                'date' => $gr->receipt_date,
                'total' => 0,
                'status' => $gr->status,
            ];
        }
        return collect($data);
    }

    public function headings(): array
    {
        return ['Tipe', 'Nomor', 'Supplier', 'Tanggal', 'Total (Rp)', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->type,
            $row->number,
            $row->supplier,
            date('d/m/Y', strtotime($row->date)),
            number_format($row->total, 0, ',', '.'),
            ucfirst($row->status),
        ];
    }
}