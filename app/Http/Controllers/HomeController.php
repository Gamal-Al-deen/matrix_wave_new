<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\Currency;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $query) => $query->publiclyVisible(),
            ])
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
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
            'banners' => $banners,
            'settings' => $settings,
            'currency' => Currency::code(),
        ]);
    }
}
