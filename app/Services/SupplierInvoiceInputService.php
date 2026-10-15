<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierInvoiceInputService
{
    private function cents($value): int
    {
        $text = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $text)) {
            throw ValidationException::withMessages(['po_supplier_id' => 'Nilai PO tidak valid.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $text, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $po = DB::table('po_suppliers')->where('id', $data['po_supplier_id'])->lockForUpdate()->first();
            if (!$po || !in_array($po->status, ['sent','confirmed','received'], true)) {
                throw ValidationException::withMessages(['po_supplier_id' => 'Pilih PO Supplier Sent, Confirmed, atau Received.']);
            }
            if (DB::table('invoice_suppliers')->where('po_supplier_id', $po->id)->exists()) {
                throw ValidationException::withMessages(['po_supplier_id' => 'PO ini sudah memiliki invoice penuh.']);
            }
            if (DB::table('invoice_suppliers')->where('invoice_number', $data['invoice_number'])->exists()) {
                throw ValidationException::withMessages(['invoice_number' => 'Nomor invoice supplier sudah digunakan.']);
            }
            if (!DB::table('suppliers')->where('id', $po->supplier_id)->exists()) {
                throw ValidationException::withMessages(['po_supplier_id' => 'Supplier PO tidak ditemukan.']);
            }
            $details = DB::table('po_supplier_details')->where('po_supplier_id', $po->id)->lockForUpdate()->get();
            if ($details->isEmpty()) {
                throw ValidationException::withMessages(['po_supplier_id' => 'PO belum memiliki detail barang.']);
            }
            $subtotal = $this->cents($po->subtotal);
            $discount = $this->cents($po->discount_amount);
            $tax = $this->cents($po->tax_amount);
            $total = $this->cents($po->total);
            $sum = 0;
            foreach ($details as $detail) {
                $quantity = (int) $detail->quantity;
                if ($quantity <= 0 || !DB::table('products')->where('id', $detail->product_id)->exists()) {
                    throw ValidationException::withMessages(['po_supplier_id' => 'Detail/product PO tidak valid.']);
                }
                $line = $this->cents($detail->subtotal);
                if ($line !== $quantity * $this->cents($detail->purchase_price)) {
                    throw ValidationException::withMessages(['po_supplier_id' => 'Subtotal detail PO tidak sesuai quantity dan harga.']);
                }
                $sum += $line;
            }
            if ($subtotal !== $sum || $discount > $subtotal || $total <= 0 || $total !== $subtotal - $discount + $tax) {
                throw ValidationException::withMessages(['po_supplier_id' => 'Total/discount/tax PO tidak konsisten. Periksa PO terlebih dahulu.']);
            }
            $id = DB::table('invoice_suppliers')->insertGetId([
                'invoice_number' => $data['invoice_number'], 'po_supplier_id' => $po->id,
                'invoice_date' => $data['invoice_date'], 'due_date' => $data['due_date'] ?? null,
                'status' => 'unpaid', 'amount' => $this->money($subtotal-$discount),
                'tax' => $this->money($tax), 'total' => $this->money($total),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($details as $detail) {
                DB::table('invoice_supplier_details')->insert([
                    'invoice_supplier_id' => $id, 'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity, 'unit_price' => $detail->purchase_price,
                    'subtotal' => $detail->subtotal, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            return $id;
        });
    }
}
