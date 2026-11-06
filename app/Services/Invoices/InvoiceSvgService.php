<?php

namespace App\Services\Invoices;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Builds a standalone SVG image from the order's historical snapshots.
 *
 * The SVG is intentionally generated server-side without a browser capture
 * dependency. Monetary values and item names come only from the order; the
 * current site name and logo are non-monetary branding metadata.
 */
class InvoiceSvgService
{
    private const WIDTH = 1000;

    private const MARGIN = 48;

    private const FONT_FAMILY = 'Tajawal, Segoe UI, Tahoma, Arial, sans-serif';

    public function render(Order $order, string $siteName, string $logoUrl, ?string $logoStoragePath = null): string
    {
        $currency = $order->currency_code;
        $money = fn (string|int|float $amount): string => number_format((float) $amount, 2).' '.$currency;
        $left = self::MARGIN;
        $right = self::WIDTH - self::MARGIN;
        $center = self::WIDTH / 2;

        $elements = [
            '<rect width="1000" height="156" fill="#0C2A34"/>',
        ];

        $logoData = $this->storedImageData($logoStoragePath)
            ?? $this->imageData($logoUrl)
            ?? $this->imageData((string) asset('images/branding/matrix-wave-logo-2x.jpg'));

        if ($logoData !== null) {
            $elements[] = '<image x="690" y="35" width="262" height="86" preserveAspectRatio="xMidYMid meet" href="'.$this->xml($logoData).'"/>';
        }

        $elements[] = $this->text($left, 63, $siteName, 'start', 'rtl', 22, '#FFFFFF', 800);
        $elements[] = $this->text($left, 95, 'فاتورة طلب', 'start', 'rtl', 16, '#61D7C8', 700);
        $elements[] = $this->text($left, 124, 'مستند من بيانات الطلب المحفوظة', 'start', 'rtl', 12, '#FFFFFF', 400, 0.72);

        $y = 198;
        $elements[] = $this->text($right, $y, 'رقم الطلب', 'start', 'rtl', 12, '#64748B', 700);
        $elements[] = $this->text($right, $y + 27, $order->order_number, 'start', 'ltr', 18, '#0C2A34', 800);
        $elements[] = $this->text($center, $y, 'تاريخ الطلب', 'middle', 'rtl', 12, '#64748B', 700);
        $elements[] = $this->text($center, $y + 27, $order->created_at->format('Y-m-d H:i'), 'middle', 'ltr', 15, '#0C2A34', 600);
        $elements[] = $this->text($left, $y, 'حالة الدفع', 'start', 'rtl', 12, '#64748B', 700);
        $elements[] = $this->text($left, $y + 29, $order->payment_status->label(), 'start', 'rtl', 15, $this->paymentColor($order->payment_status), 800);

        $y = 254;
        $elements[] = '<line x1="48" y1="'.$y.'" x2="952" y2="'.$y.'" stroke="#E2E8F0"/>';

        $y = 292;
        $elements[] = $this->text($right, $y, 'بيانات العميل والتوصيل', 'start', 'rtl', 16, '#0C2A34', 800);
        $y += 28;

        $customerLines = [
            'الاسم: '.$order->customer_name_snapshot,
            'الهاتف: '.$order->customer_phone_snapshot,
        ];

        if (filled($order->customer_email_snapshot)) {
            $customerLines[] = 'البريد الإلكتروني: '.$order->customer_email_snapshot;
        }

        $customerLines[] = 'عنوان التوصيل: '.preg_replace('/\R/u', ' ', trim($order->delivery_address_snapshot));

        foreach ($customerLines as $line) {
            foreach ($this->wrap($line, 84) as $wrappedLine) {
                $elements[] = $this->text($right, $y, $wrappedLine, 'start', 'rtl', 13, '#18343A', 500);
                $y += 22;
            }
        }

        $y += 18;
        $elements[] = '<rect x="48" y="'.$y.'" width="904" height="38" rx="8" fill="#F4F8F8"/>';
        $headerY = $y + 24;
        $elements[] = $this->text($right, $headerY, 'المنتج', 'start', 'rtl', 12, '#475569', 800);
        $elements[] = $this->text(610, $headerY, 'الكمية', 'middle', 'rtl', 12, '#475569', 800);
        $elements[] = $this->text(400, $headerY, 'سعر الوحدة', 'middle', 'rtl', 12, '#475569', 800);
        $elements[] = $this->text(150, $headerY, 'الإجمالي', 'middle', 'rtl', 12, '#475569', 800);
        $y += 38;

        $rowIndex = 0;
        foreach ($order->items as $item) {
            $nameLines = $this->wrap($item->product_name_snapshot, 38);
            $rowHeight = max(38, (count($nameLines) * 20) + 18);

            if ($rowIndex % 2 === 1) {
                $elements[] = '<rect x="48" y="'.$y.'" width="904" height="'.$rowHeight.'" fill="#FAFCFC"/>';
            }

            $nameY = $y + 24;
            foreach ($nameLines as $nameLine) {
                $elements[] = $this->text($right, $nameY, $nameLine, 'start', 'rtl', 13, '#18343A', 700);
                $nameY += 20;
            }

            $elements[] = $this->text(610, $y + 24, (string) $item->quantity, 'middle', 'ltr', 13, '#18343A', 600);
            $elements[] = $this->text(400, $y + 24, $money($item->unit_price), 'middle', 'ltr', 12, '#18343A', 500);
            $elements[] = $this->text(150, $y + 24, $money($item->line_total), 'middle', 'ltr', 13, '#0C2A34', 800);

            $y += $rowHeight;
            $elements[] = '<line x1="48" y1="'.$y.'" x2="952" y2="'.$y.'" stroke="#E2E8F0"/>';
            $rowIndex++;
        }

        if ($order->items->isEmpty()) {
            $elements[] = $this->text($center, $y + 24, 'لا توجد منتجات محفوظة في هذا الطلب.', 'middle', 'rtl', 13, '#64748B', 500);
            $y += 52;
        }

        $y += 34;
        $totals = [
            ['المجموع الفرعي', $money($order->subtotal)],
        ];

        if ((float) $order->delivery_fee > 0) {
            $totals[] = ['رسوم التوصيل', $money($order->delivery_fee)];
        }

        $totals[] = ['الإجمالي', $money($order->total)];

        foreach ($totals as $index => [$label, $value]) {
            $isTotal = $index === count($totals) - 1;

            $elements[] = $this->text($right, $y, $label, 'start', 'rtl', $isTotal ? 16 : 13, '#0C2A34', $isTotal ? 800 : 500);
            $elements[] = $this->text($left, $y, $value, 'start', 'ltr', $isTotal ? 16 : 13, '#0C2A34', $isTotal ? 800 : 500);

            $y += $isTotal ? 30 : 24;
        }

        if ((float) $order->delivery_fee <= 0) {
            $elements[] = $this->text($right, $y + 2, 'لم تُسجّل رسوم توصيل على هذا الطلب.', 'start', 'rtl', 11, '#64748B', 400);
            $y += 24;
        }

        $y += 24;

        foreach ($this->wrap('هذه الفاتورة مبنية على بيانات الطلب المحفوظة عند إنشائه، وتظل أسعارها وعملتها دون تغيير عند تعديل بيانات المنتجات أو إعدادات المتجر.', 92) as $footerLine) {
            $elements[] = $this->text($right, $y, $footerLine, 'start', 'rtl', 11, '#64748B', 400);
            $y += 20;
        }

        $height = max(620, $y + 30);

        $svg = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="'.$height.'" viewBox="0 0 1000 '.$height.'" role="img" aria-labelledby="invoice-title">'."\n";
        $svg .= '<title id="invoice-title">فاتورة الطلب '.$this->xml($order->order_number).'</title>'."\n";
        $svg .= '<rect width="1000" height="'.$height.'" fill="#FFFFFF"/>'."\n";
        $svg .= implode("\n", $elements)."\n";
        $svg .= '</svg>';

        return $svg;
    }

    private function text(
        float|int $x,
        float|int $y,
        string $value,
        string $anchor,
        string $direction,
        int $size,
        string $color,
        int $weight,
        float $opacity = 1.0,
    ): string {
        return '<text x="'.$x.'" y="'.$y.'" text-anchor="'.$anchor.'" direction="'.$direction.'"'
            .' font-family="'.self::FONT_FAMILY.'" font-size="'.$size.'" font-weight="'.$weight.'"'
            .' fill="'.$color.'" opacity="'.$opacity.'" xml:lang="ar">'.$this->xml($value).'</text>';
    }

    private function paymentColor(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Unpaid => '#B45309',
            PaymentStatus::AwaitingVerification => '#0369A1',
            PaymentStatus::PartiallyPaid => '#C2410C',
            PaymentStatus::Paid => '#15803D',
            PaymentStatus::Refunded => '#475569',
        };
    }

    /**
     * @return list<string>
     */
    private function wrap(string $value, int $maxCharacters): array
    {
        $lines = [];

        foreach (preg_split('/\R/u', $value) ?: [] as $paragraph) {
            $words = preg_split('/\s+/u', trim($paragraph)) ?: [];

            if ($words === []) {
                $lines[] = '';

                continue;
            }

            $line = '';

            foreach ($words as $word) {
                $candidate = $line === '' ? $word : $line.' '.$word;

                if (mb_strlen($candidate) <= $maxCharacters) {
                    $line = $candidate;

                    continue;
                }

                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }

                while (mb_strlen($word) > $maxCharacters) {
                    $lines[] = mb_substr($word, 0, $maxCharacters);
                    $word = mb_substr($word, $maxCharacters);
                }

                $line = $word;
            }

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines === [] ? [''] : $lines;
    }

    /**
     * Read an uploaded logo from the configured public storage disk.
     */
    private function storedImageData(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        try {
            $disk = Storage::disk((string) config('media.disk', 'supabase'));

            if (! $disk->exists($path)) {
                return null;
            }

            $mimeType = $disk->mimeType($path);
            $contents = $disk->get($path);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($mimeType) || ! is_string($contents) || ! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function imageData(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $relativePath = ltrim(rawurldecode($path), '/');
        $publicRoot = realpath(public_path());
        $fullPath = realpath(public_path($relativePath));

        if ($publicRoot === false || $fullPath === false || ! str_starts_with($fullPath, $publicRoot.DIRECTORY_SEPARATOR)) {
            return null;
        }

        $mimeType = mime_content_type($fullPath);
        $contents = file_get_contents($fullPath);

        if (! is_string($mimeType) || ! is_string($contents) || ! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
