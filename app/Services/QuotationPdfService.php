<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotationPdfService
{
    /**
     * Render the quotation to a PDF with DomPDF (pure PHP, no external process) —
     * this app runs on shared hosting where a local headless Chrome can't spawn
     * enough threads to survive the host's process limits. The header/footer curves
     * are pre-rendered static images (DomPDF can't reproduce the live CSS drop-shadow
     * filter), with only the dynamic text/logo overlaid at render time.
     *
     * @return string absolute path to the generated PDF
     */
    public function generate(Quotation $quotation): string
    {
        $quotation->loadMissing('items');

        $settings = SiteSetting::pluck('value', 'key');

        $logoUrl = $this->inlineImage($settings->get('company', [])['logo_url'] ?? null);

        $dir = storage_path('app/quotations');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = preg_replace('/[^A-Za-z0-9_-]/', '_', $quotation->quote_number);
        $pdfPath = "{$dir}/{$basename}.pdf";

        Pdf::loadView('pdf.quotation-dompdf', [
            'q' => $quotation,
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
