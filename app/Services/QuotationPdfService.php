<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class QuotationPdfService
{
    /**
     * Render the quotation to a PDF using headless Chrome, so the output matches
     * the on-screen/print design exactly (drop-shadow filters, gradients, flexbox —
     * none of which a pure-PHP PDF renderer reproduces reliably).
     *
     * @return string absolute path to the generated PDF
     */
    public function generate(Quotation $quotation): string
    {
        $quotation->loadMissing('items');

        $settings = SiteSetting::pluck('value', 'key');

        // Inline the logo as a data URI instead of an http(s) URL back to this app.
        // On a single-threaded dev server (`php artisan serve`), the request that's
        // generating this PDF is what's blocking — so Chrome trying to fetch the logo
        // from that same server deadlocks until this process's own timeout kills it.
        $company = $settings->get('company', []);
        if (! empty($company['logo_url'])) {
            $company['logo_url'] = $this->inlineImage($company['logo_url']) ?? $company['logo_url'];
            $settings->put('company', $company);
        }

        $html = view('pdf.quotation', [
            'q' => $quotation,
            'settings' => $settings,
        ])->render();

        $dir = storage_path('app/quotations');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = preg_replace('/[^A-Za-z0-9_-]/', '_', $quotation->quote_number);
        $htmlPath = "{$dir}/{$basename}.html";
        $pdfPath = "{$dir}/{$basename}.pdf";

        file_put_contents($htmlPath, $html);

        try {
            $process = new Process([
                config('services.chrome.path'),
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
                '--print-to-pdf='.$pdfPath,
                '--no-pdf-header-footer',
                'file://'.$htmlPath,
            ]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($pdfPath)) {
                Log::error('Quotation PDF generation failed', [
                    'quote_number' => $quotation->quote_number,
                    'error' => $process->getErrorOutput(),
                ]);

                throw new RuntimeException('Failed to generate quotation PDF.');
            }
        } finally {
            @unlink($htmlPath);
        }

        return $pdfPath;
    }

    /**
     * Resolve a locally-hosted (this app's own /storage) image URL to a base64 data
     * URI by reading it straight off disk, bypassing HTTP entirely. Returns null for
     * anything else (external URL, missing file) so the caller can fall back safely.
     */
    private function inlineImage(string $url): ?string
    {
        $relativePath = ImagePipeline::relativePathFromUrl($url);

        if ($relativePath === null || ! Storage::disk('public')->exists($relativePath)) {
            return null;
        }

        $contents = Storage::disk('public')->get($relativePath);
        $mime = Storage::disk('public')->mimeType($relativePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
