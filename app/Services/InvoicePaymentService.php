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

        $payment = Payment::create($data);

        if ($payment->invoice) {
            $invoice = $payment->invoice;
            $invoice->paid_amount += $payment->amount;
            $invoice->payment_method = $payment->method;
            $invoice->status = $invoice->paid_amount >= $invoice->amount ? 'paid' : 'partial';
            $invoice->save();
        }

        return $payment;
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
        $unitPrice = (int) Arr::get($item, 'unit_price', 0);

        $invoice->items()->create([
            'description' => Arr::get($item, 'description'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ]);
    }

    private function totals(array $items, bool $vatIncluded): array
    {
        $subtotal = collect($items)->sum(fn (array $item) => ((int) ($item['quantity'] ?? 1)) * ((int) ($item['unit_price'] ?? 0)));
        $tax = $vatIncluded ? (int) round($subtotal * 0.16) : 0;

        return ['subtotal' => $subtotal, 'tax' => $tax, 'amount' => $subtotal + $tax];
    }
}
