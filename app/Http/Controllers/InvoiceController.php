<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\Invoices\InvoiceSvgService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function show(Order $order): View
    {
        $this->authorize('viewInvoice', $order);

        $order->load('items');

        return view('invoices.show', [
            'order' => $order,
            ...$this->branding(),
        ]);
    }

    public function image(Order $order, InvoiceSvgService $svgService): Response
    {
        $this->authorize('viewInvoice', $order);

        $order->load('items');
        $branding = $this->branding();
        $svg = $svgService->render(
            $order,
            $branding['siteName'],
            $branding['logoUrl'],
            $branding['logoStoragePath'],
        );
        $filename = 'invoice-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $order->order_number).'.svg';

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * @return array{siteName: string, logoUrl: string, logoStoragePath: ?string, contactPhone: ?string, contactEmail: ?string}
     */
    private function branding(): array
    {
        $settings = SiteSetting::current();
        $siteName = trim((string) $settings?->site_name);

        return [
            'siteName' => $siteName !== '' ? $siteName : (string) config('app.name', 'Matrix Wave'),
            'logoUrl' => $this->assetUrl(
                $settings?->logo_public_url,
                'images/branding/matrix-wave-logo-2x.jpg',
            ),
            'logoStoragePath' => filled($settings?->logo_storage_path) ? (string) $settings->logo_storage_path : null,
            'contactPhone' => filled($settings?->contact_phone) ? (string) $settings->contact_phone : null,
            'contactEmail' => filled($settings?->contact_email) ? (string) $settings->contact_email : null,
        ];
    }

    private function assetUrl(?string $url, string $fallback): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return asset($fallback);
        }

        if (Str::startsWith($url, ['http://', 'https://', '//', '/'])) {
            return $url;
        }

        return asset($url);
    }
}
