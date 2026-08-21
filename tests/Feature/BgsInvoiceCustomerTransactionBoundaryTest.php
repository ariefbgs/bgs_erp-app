<?php

namespace Tests\Feature;

use ReflectionClass;
use Tests\TestCase;

class BgsInvoiceCustomerTransactionBoundaryTest extends TestCase
{
    private function methodSource(string $method): string
    {
        $reflection = new ReflectionClass(
            \App\Http\Controllers\InvoiceCustomerController::class
        );

        $m = $reflection->getMethod($method);
        $file = file($m->getFileName());

        return implode(
            '',
            array_slice(
                $file,
                $m->getStartLine() - 1,
                $m->getEndLine() - $m->getStartLine() + 1
            )
        );
    }

    private function assertAtomicMutationMethod(string $method): void
    {
        $source = $this->methodSource($method);

        $begin = strpos(
            $source,
            'DB::beginTransaction()'
        );

        $sync = strpos(
            $source,
            '->updateInvoiceStatus()'
        );

        $commit = strpos(
            $source,
            'DB::commit()'
        );

        $this->assertNotFalse(
            $begin,
            "{$method}() must begin a transaction."
        );

        $this->assertNotFalse(
            $sync,
            "{$method}() must use canonical PO invoice synchronization."
        );

        $this->assertNotFalse(
            $commit,
            "{$method}() must commit the transaction."
        );

        if (
            $begin !== false &&
            $sync !== false &&
            $commit !== false
        ) {
            $this->assertLessThan(
                $sync,
                $begin,
                "{$method}(): transaction must begin before aggregate synchronization."
            );

            $this->assertLessThan(
                $commit,
                $sync,
                "{$method}(): aggregate synchronization must occur before commit."
            );
        }

        $this->assertStringNotContainsString(
            'updatePoCustomerInvoiceStatus',
            $source,
            "{$method}() must not use the legacy duplicate aggregate helper."
        );
    }

    public function test_store_has_atomic_invoice_and_po_aggregate_boundary(): void
    {
        $this->assertAtomicMutationMethod('store');
    }

    public function test_update_has_atomic_invoice_and_po_aggregate_boundary(): void
    {
        $this->assertAtomicMutationMethod('update');
    }

    public function test_cancel_has_atomic_invoice_and_po_aggregate_boundary(): void
    {
        $this->assertAtomicMutationMethod('cancel');
    }

    public function test_destroy_has_atomic_invoice_and_po_aggregate_boundary(): void
    {
        $this->assertAtomicMutationMethod('destroy');
    }
}