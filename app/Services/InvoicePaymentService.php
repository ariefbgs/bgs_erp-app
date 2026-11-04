<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoicePaymentService
{
    public function markPaid(string $kind, int $id, string $paymentDate, int $userId, ?string $reference = null): void
    {
        if (!in_array($kind, ['customer', 'supplier'], true)) {
            throw ValidationException::withMessages(['invoice_id' => 'Jenis invoice tidak valid.']);
        }
        DB::transaction(function () use ($kind, $id, $paymentDate, $userId, $reference) {
            $table = $kind === 'customer' ? 'invoice_customers' : 'invoice_suppliers';
            $field = $kind === 'customer' ? 'payment_status' : 'status';
            $invoice = DB::table($table)->where('id', $id)->lockForUpdate()->first();
            if (!$invoice) {
                throw ValidationException::withMessages(['invoice_id' => 'Invoice tidak ditemukan.']);
            }
            $valid = $kind === 'customer'
                ? ($invoice->payment_status === 'sent' && $invoice->status !== 'cancelled')
                : in_array($invoice->status, ['unpaid', 'overdue'], true);
            if (!$valid || DB::table('invoice_payment_confirmations')->where('invoice_kind', $kind)->where('invoice_id', $id)->exists()) {
                throw ValidationException::withMessages(['invoice_id' => 'Invoice sudah Paid atau tidak dapat dibayar.']);
            }
            DB::table('invoice_payment_confirmations')->insert([
                'invoice_kind' => $kind, 'invoice_id' => $id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_total' => $invoice->total,
                'payment_date' => $paymentDate, 'confirmed_by' => $userId,
                'reference' => $reference, 'previous_status' => $invoice->{$field},
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table($table)->where('id', $id)->update([$field => 'paid', 'updated_at' => now()]);
        });
    }
}
