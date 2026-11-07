<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\SiteSetting;

final class OrderWhatsAppService
{
    /**
     * Build the standard Click to Chat URL from the saved order and configured number.
     */
    public function urlFor(Order $order): ?string
    {
        $settings = $this->settings();
        $number = $this->normalizeNumber($settings?->whatsapp_number);

        if ($number === null) {
            return null;
        }

        $message = $this->messageFor($order, $settings?->site_name);

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public function isConfigured(): bool
    {
        return $this->normalizeNumber($this->settings()?->whatsapp_number) !== null;
    }

    /**
     * Build a general Click to Chat URL for contacting the store from the site.
     */
    public function contactUrl(?string $siteName = null): ?string
    {
        $settings = $this->settings();
        $number = $this->normalizeNumber($settings?->whatsapp_number);

        if ($number === null) {
            return null;
        }

        $siteName = is_string($siteName) && trim($siteName) !== ''
            ? $siteName
            : (string) config('app.name', 'Matrix Wave');

        $message = 'مرحبًا، أنا زائر من موقع '.$siteName.' وأرغب في التواصل معكم للاستفسار عن منتجاتكم.';

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public function messageFor(Order $order, ?string $siteName = null): string
    {
        $order->loadMissing(['items', 'wallet']);

        $siteName = is_string($siteName) && trim($siteName) !== ''
            ? $siteName
            : (string) config('app.name', 'Matrix Wave');

        $currency = $order->currency_code;
        $lines = [
            'مرحبًا، أود متابعة الطلب التالي من '.$siteName.':',
            '',
            'رقم الطلب: '.$order->order_number,
            'الاسم: '.$order->customer_name_snapshot,
            'الهاتف: '.$order->customer_phone_snapshot,
        ];

        if ($order->customer_email_snapshot) {
            $lines[] = 'البريد الإلكتروني: '.$order->customer_email_snapshot;
        }

        $lines[] = 'عنوان التوصيل:';
        $lines[] = $order->delivery_address_snapshot;
        $lines[] = '';
        $lines[] = 'المنتجات:';

        foreach ($order->items as $index => $item) {
            $lines[] = ($index + 1).'. '.$item->product_name_snapshot;
            $lines[] = 'الكمية: '.$item->quantity;
            $lines[] = 'سعر الوحدة: '.$this->money($item->unit_price, $currency);
            $lines[] = 'إجمالي السطر: '.$this->money($item->line_total, $currency);
            $lines[] = '';
        }

        $lines[] = 'المجموع الفرعي: '.$this->money($order->subtotal, $currency);

        if ((float) $order->delivery_fee > 0) {
            $lines[] = 'رسوم التوصيل: '.$this->money($order->delivery_fee, $currency);
        }

        $lines[] = 'الإجمالي: '.$this->money($order->total, $currency);
        $lines[] = 'المحفظة المختارة: '.$this->walletLabel($order);

        if ($order->notes) {
            $lines[] = 'ملاحظات: '.$order->notes;
        }

        return implode("\n", $lines);
    }

    private function settings(): ?SiteSetting
    {
        return SiteSetting::current();
    }

    private function normalizeNumber(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        return is_string($digits) && $digits !== '' ? $digits : null;
    }

    private function walletLabel(Order $order): string
    {
        if ($order->wallet) {
            return $order->wallet->name;
        }

        return $order->wallet_id
            ? 'محفظة غير متاحة حاليًا'
            : 'بدون محفظة';
    }

    private function money(string $amount, string $currency): string
    {
        return number_format((float) $amount, 2).' '.$currency;
    }
}
