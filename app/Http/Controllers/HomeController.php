<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Orders\OrderWhatsAppService;
use App\Support\Currency;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $query) => $query->publiclyVisible(),
            ])
            ->orderBy('name')
            ->get();

        $categorySlug = trim((string) $request->query('category', ''));

        $selectedCategory = $categorySlug !== ''
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->when($categorySlug !== '', function (Builder $query) use ($selectedCategory): void {
                if ($selectedCategory === null) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where('category_id', $selectedCategory->id);
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $banners = Banner::query()
            ->publiclyVisible()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        $settings = SiteSetting::current();

        return view('public.home', [
            'categories' => $categories,
            'products' => $products,
            'selectedCategory' => $selectedCategory,
            'categorySlug' => $categorySlug,
            'banners' => $banners,
            'settings' => $settings,
            'currency' => Currency::code(),
            'whatsappUrl' => app(OrderWhatsAppService::class)->contactUrl($settings?->displayName()),
        ]);
    }
}
