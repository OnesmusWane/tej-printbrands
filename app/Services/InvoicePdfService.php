<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Render the invoice to a PDF with DomPDF — same approach and the same
     * pre-rendered header/footer artwork as QuotationPdfService (the curve
     * background is brand-generic, no "QUOTATION"/"INVOICE" text baked in).
     *
     * @return string absolute path to the generated PDF
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'payments']);

        $settings = SiteSetting::pluck('value', 'key');

        $logoUrl = $this->inlineImage($settings->get('company', [])['logo_url'] ?? null);

        $dir = storage_path('app/invoices');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = preg_replace('/[^A-Za-z0-9_-]/', '_', $invoice->invoice_number);
        $pdfPath = "{$dir}/{$basename}.pdf";

        Pdf::loadView('pdf.invoice-dompdf', [
            'inv' => $invoice,
            'settings' => $settings,
            'logoUrl' => $logoUrl,
            'headerBg' => public_path('images/pdf/quotation-header-bg.png'),
            'footerBg' => public_path('images/pdf/quotation-footer-bg.png'),
        ])
            ->setPaper('a4', 'portrait')
            ->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Resolve a locally-hosted (this app's own /storage) image URL to a base64 data
     * URI by reading it straight off disk, bypassing HTTP entirely. Returns null for
     * anything else (external URL, missing file, or no logo set).
     */
    private function inlineImage(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $relativePath = ImagePipeline::relativePathFromUrl($url);

        if ($relativePath === null || ! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        $contents = Storage::disk('public')->get($relativePath);
        $mime = Storage::disk('public')->mimeType($relativePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
