<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use App\Services\GoogleReviewsService;
use Inertia\Inertia;

class MenuController extends Controller
{
  public function index()
  {
    $categories = Category::active()
      ->ordered()
      ->with([
        'products' => function ($query) {
          $query->available()
            ->ordered()
            ->with([
              'variants' => function ($q) {
                $q->ordered();
              },
              'addons' => function ($q) {
                $q->available();
              }
            ]);
        }
      ])
      ->get();

    $featuredProducts = Product::featured()
      ->available()
      ->with([
        'category',
        'variants' => function ($q) {
          $q->ordered();
        },
        'addons' => function ($q) {
          $q->available();
        }
      ])
      ->limit(6)
      ->get();

    // Generate JSON-LD structured data for restaurant
    $domain = request()->getHost();
    $baseUrl = request()->getScheme() . '://' . $domain;

    $structuredData = [
      '@context' => 'https://schema.org',
      '@type' => 'Restaurant',
      'name' => config('app.name'),
      'url' => $baseUrl,
      'description' => 'Zamów pizzę online! Świeże składniki, szybka dostawa.',
      'servesCuisine' => 'Pizza',
      'priceRange' => '$$',
      'acceptsReservations' => (bool) Setting::get('reservations_enabled', false),
      'hasMenu' => [
        '@type' => 'Menu',
        'hasMenuSection' => $categories->map(function ($category) use ($baseUrl) {
          return [
            '@type' => 'MenuSection',
            'name' => $category->name,
            'hasMenuItem' => $category->products->map(function ($product) use ($baseUrl) {
              $variant = $product->variants->first();
              return [
                '@type' => 'MenuItem',
                'name' => $product->name,
                'description' => $product->description,
                'image' => $product->image ? $baseUrl . '/storage/' . $product->image : null,
                'offers' => [
                  '@type' => 'Offer',
                  'price' => $variant ? $variant->price : 0,
                  'priceCurrency' => 'PLN',
                ],
              ];
            })->toArray(),
          ];
        })->toArray(),
      ],
    ];

    $googleData = app(GoogleReviewsService::class)->getReviews(6);

    return Inertia::render('Tenant/Client/Menu', [
      'categories' => $categories,
      'featuredProducts' => $featuredProducts,
      'googleReviews' => $googleData['reviews'],
      'googleRating' => $googleData['rating'],
      'googleTotalReviews' => $googleData['total_reviews'],
      'structuredData' => $structuredData,
    ]);
  }

  public function show(Product $product)
  {
    if (!$product->is_available) {
        abort(404);
    }

    $product->load([
      'category',
      'variants' => function ($q) {
        $q->ordered();
      },
      'addons' => function ($q) {
        $q->available();
      }
    ]);

    return response()->json($product);
  }
}
