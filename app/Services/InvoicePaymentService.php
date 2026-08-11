<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Arr;

class InvoicePaymentService
{
    public function recordPayment(array $data): Payment
    {
        $data['payment_number'] ??= 'PAY-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));
        $data['paid_at'] ??= now();

        if (! empty($data['invoice_id']) && empty($data['email'])) {
            $data['email'] = Invoice::find($data['invoice_id'])?->email;
        }

        $payment = Payment::create($data);

        if ($payment->invoice) {
            $invoice = $payment->invoice;
            $this->applyPaidAmountDelta($invoice, (float) $payment->amount);
            $invoice->payment_method = $payment->method;
            $invoice->save();
        }

        return $payment;
    }

    /**
     * Update a payment's content. Items, when the amount or linked invoice changes,
     * the previously-linked invoice's paid_amount/status is rolled back first and the
     * (possibly different) invoice is reapplied, so totals never drift out of sync.
     */
    public function updatePayment(Payment $payment, array $data): Payment
    {
        $oldInvoice = $payment->invoice;
        $oldAmount = (float) $payment->amount;

        $payment->update($data);
        $payment->refresh();

        $newInvoice = $payment->invoice;

        if ($oldInvoice && (! $newInvoice || $newInvoice->isNot($oldInvoice))) {
            $this->applyPaidAmountDelta($oldInvoice, -$oldAmount);
            $oldInvoice->save();
        }

        if ($newInvoice) {
            $delta = ($oldInvoice && $newInvoice->is($oldInvoice))
                ? ((float) $payment->amount - $oldAmount)
                : (float) $payment->amount;

            $this->applyPaidAmountDelta($newInvoice, $delta);
            $newInvoice->payment_method = $payment->method;
            $newInvoice->save();
        }

        return $payment->load('invoice');
    }

    /**
     * Remove a payment, rolling its amount back off the linked invoice's
     * paid_amount/status (if any) before deleting it.
     */
    public function deletePayment(Payment $payment): void
    {
        if ($payment->invoice) {
            $invoice = $payment->invoice;
            $this->applyPaidAmountDelta($invoice, -(float) $payment->amount);
            $invoice->save();
        }

        $payment->delete();
    }

    private function applyPaidAmountDelta(Invoice $invoice, float $delta): void
    {
        $paid = max(0, round((float) $invoice->paid_amount + $delta, 2));
        $invoice->paid_amount = $paid;
        $invoice->status = $paid <= 0 ? 'unpaid' : ($paid >= (float) $invoice->amount ? 'paid' : 'partial');
    }

    public function createInvoice(array $data): Invoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $vatIncluded = (bool) ($data['vat_included'] ?? true);

        $data['invoice_number'] ??= 'INV-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));
        $data['payment_method'] ??= 'Pending';
        $data = array_merge($data, $this->totals($items, $vatIncluded));
        $data['vat_included'] = $vatIncluded;

        $invoice = Invoice::create($data);

        foreach ($items as $item) {
            $this->createItem($invoice, $item);
        }

        return $invoice->load('items');
    }

    /**
     * Update an invoice's content. Items, when provided, are fully replaced
     * (delete-and-recreate) rather than diffed — mirrors QuotationService::update.
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        $items = $data['items'] ?? null;
        unset($data['items']);

        if (array_key_exists('payment_method', $data) && $data['payment_method'] === null) {
            $data['payment_method'] = 'Pending';
        }

        if ($items !== null) {
            $vatIncluded = (bool) ($data['vat_included'] ?? $invoice->vat_included);
            $data = array_merge($data, $this->totals($items, $vatIncluded));
            $data['vat_included'] = $vatIncluded;
        }

        $invoice->update($data);

        if ($items !== null) {
            $invoice->items()->delete();

            foreach ($items as $item) {
                $this->createItem($invoice, $item);
            }
        }

        return $invoice->load('items');
    }

    private function createItem(Invoice $invoice, array $item): void
    {
        $quantity = (int) Arr::get($item, 'quantity', 1);
        $unitPrice = round((float) Arr::get($item, 'unit_price', 0), 2);

        $invoice->items()->create([
            'description' => Arr::get($item, 'description'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => round($quantity * $unitPrice, 2),
        ]);
    }

    private function totals(array $items, bool $vatIncluded): array
    {
        $subtotal = round(collect($items)->sum(fn (array $item) => ((int) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0))), 2);
        $tax = $vatIncluded ? round($subtotal * 0.16, 2) : 0;

        return ['subtotal' => $subtotal, 'tax' => $tax, 'amount' => round($subtotal + $tax, 2)];
    }
}
