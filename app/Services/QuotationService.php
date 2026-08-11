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
            $unitPrice = (int) Arr::get($item, 'unit_price', 0);
            $quotation->items()->create([
                'description' => Arr::get($item, 'description'),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $quantity * $unitPrice,
            ]);
        }

        return $quotation->load('items');
    }

    private function totals(array $items, bool $vatIncluded): array
    {
        $subtotal = collect($items)->sum(fn (array $item) => ((int) ($item['quantity'] ?? 1)) * ((int) ($item['unit_price'] ?? 0)));
        $tax = $vatIncluded ? (int) round($subtotal * 0.16) : 0;

        return ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax];
    }
}
