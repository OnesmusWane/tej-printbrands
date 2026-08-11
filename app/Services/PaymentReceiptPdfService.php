<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PaymentReceiptPdfService
{
    /**
     * Render the payment to an A5-landscape receipt PDF with DomPDF — same
     * inline-logo approach as InvoicePdfService/QuotationPdfService.
     *
     * @return string absolute path to the generated PDF
     */
    public function generate(Payment $payment): string
    {
        $payment->loadMissing('invoice');

        $settings = SiteSetting::pluck('value', 'key');

        $logoUrl = $this->inlineImage($settings->get('company', [])['logo_url'] ?? null);

        $dir = storage_path('app/receipts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = preg_replace('/[^A-Za-z0-9_-]/', '_', $payment->payment_number);
        $pdfPath = "{$dir}/{$basename}.pdf";

        $method = strtolower(str_replace([' ', '-'], '_', $payment->method ?? ''));

        Pdf::loadView('pdf.receipt-dompdf', [
            'companyName' => $settings->get('company', [])['name'] ?? 'Tej Printbrands',
            'logoUrl' => $logoUrl,
            'phone' => $settings->get('contact', [])['phone'] ?? '',
            'phoneSecondary' => $settings->get('contact', [])['phone_secondary'] ?? '',
            'email' => $settings->get('contact', [])['email'] ?? '',
            'website' => $settings->get('contact', [])['website'] ?? '',
            'receiptNo' => $payment->payment_number,
            'clientName' => $payment->client,
            'amount' => (float) $payment->amount,
            'amountWords' => $this->amountInWords((float) $payment->amount),
            'paidDate' => ($payment->paid_at ?? $payment->created_at)->format('d F Y'),
            'invoiceRef' => $payment->invoice?->invoice_number ?? ($payment->invoice_id ? "Invoice #{$payment->invoice_id}" : ''),
            'reference' => $payment->reference,
            'isCash' => $method === 'cash',
            'isMpesa' => $method === 'mpesa',
            'isCheque' => in_array($method, ['bank_transfer', 'cheque'], true),
        ])
            ->setPaper('a5', 'landscape')
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

    private function amountInWords(float $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $tw = function (int $num) use (&$tw, $ones, $tens): string {
            if ($num === 0) {
                return '';
            }
            if ($num < 20) {
                return $ones[$num];
            }
            if ($num < 100) {
                return $tens[intdiv($num, 10)].($num % 10 ? '-'.$ones[$num % 10] : '');
            }
            if ($num < 1000) {
                return $ones[intdiv($num, 100)].' Hundred'.($num % 100 ? ' '.$tw($num % 100) : '');
            }
            if ($num < 1000000) {
                return $tw(intdiv($num, 1000)).' Thousand'.($num % 1000 ? ' '.$tw($num % 1000) : '');
            }

            return $tw(intdiv($num, 1000000)).' Million'.($num % 1000000 ? ' '.$tw($num % 1000000) : '');
        };

        $whole = (int) floor($n);
        $cents = (int) round(($n - $whole) * 100);

        return ($tw($whole) ?: 'Zero').' Shillings'.($cents > 0 ? ' and '.$tw($cents).' Cents' : ' Only');
    }
}
