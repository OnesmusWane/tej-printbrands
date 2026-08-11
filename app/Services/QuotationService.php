<?php

namespace App\Services;

use App\Models\Quotation;
use Illuminate\Support\Arr;

class QuotationService
{
    public function create(array $data): Quotation
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $vatIncluded = (bool) ($data['vat_included'] ?? true);

        $data['quote_number'] ??= 'QT-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -4));
        $data = array_merge($data, $this->totals($items, $vatIncluded));
        $data['vat_included'] = $vatIncluded;

        $quotation = Quotation::create($data);

        foreach ($items as $item) {
            $quantity = (int) Arr::get($item, 'quantity', 1);
            $unitPrice = round((float) Arr::get($item, 'unit_price', 0), 2);
            $quotation->items()->create([
                'description' => Arr::get($item, 'description'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ]);
        }

        return $quotation->load('items');
    }

    /**
     * Update a quotation's content. Items, when provided, are fully replaced
     * (delete-and-recreate) rather than diffed — simpler and correct, since
     * quotation line items have no identity that needs preserving across edits.
     */
    public function update(Quotation $quotation, array $data): Quotation
    {
        $items = $data['items'] ?? null;
        unset($data['items']);

        if ($items !== null) {
            $vatIncluded = (bool) ($data['vat_included'] ?? $quotation->vat_included);
            $data = array_merge($data, $this->totals($items, $vatIncluded));
            $data['vat_included'] = $vatIncluded;
        }

        $quotation->update($data);

        if ($items !== null) {
            $quotation->items()->delete();

            foreach ($items as $item) {
                $quantity = (int) Arr::get($item, 'quantity', 1);
                $unitPrice = round((float) Arr::get($item, 'unit_price', 0), 2);
                $quotation->items()->create([
                    'description' => Arr::get($item, 'description'),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($quantity * $unitPrice, 2),
                ]);
            }
        }

        return $quotation->load('items');
    }

    private function totals(array $items, bool $vatIncluded): array
    {
        $subtotal = round(collect($items)->sum(fn (array $item) => ((int) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0))), 2);
        $tax = $vatIncluded ? round($subtotal * 0.16, 2) : 0;

        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => round($subtotal + $tax, 2)];
    }
}
