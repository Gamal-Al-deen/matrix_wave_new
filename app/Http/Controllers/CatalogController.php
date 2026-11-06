<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Currency;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const PER_PAGE = 12;

    private const SORTS = ['newest', 'price_asc', 'price_desc'];

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $query = mb_substr($query, 0, 100);

        $categorySlug = trim((string) $request->query('category', ''));
        $sort = (string) $request->query('sort', 'newest');

        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        $categories = Category::query()
            ->active()
            ->withCount([
                'products' => fn (Builder $categoryQuery) => $categoryQuery->publiclyVisible(),
            ])
            ->orderBy('name')
            ->get();

        $selectedCategory = $categorySlug !== ''
            ? $categories->firstWhere('slug', $categorySlug)
            : null;

        $products = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->when($query !== '', function (Builder $builder) use ($query): void {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);

                $builder->whereLike('name', '%'.$escaped.'%');
            })
            ->when($categorySlug !== '', function (Builder $builder) use ($selectedCategory): void {
                if ($selectedCategory === null) {
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->where('category_id', $selectedCategory->id);
                }
            })
            ->when($sort === 'price_asc', fn (Builder $builder) => $builder->orderBy('price')->orderByDesc('created_at'))
            ->when($sort === 'price_desc', fn (Builder $builder) => $builder->orderByDesc('price')->orderByDesc('created_at'))
            ->when($sort === 'newest', fn (Builder $builder) => $builder->latest())
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('public.products.index', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'query' => $query,
            'sort' => $sort,
            'currency' => Currency::code(),
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with(['category', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::query()
            ->publiclyVisible()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with(['category', 'images'])
            ->latest()
            ->limit(4)
            ->get();

        return view('public.products.show', [
            'product' => $product,
            'related' => $related,
            'currency' => Currency::code(),
        ]);
    }
}
