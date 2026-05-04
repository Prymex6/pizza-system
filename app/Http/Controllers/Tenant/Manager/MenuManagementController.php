<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Category;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Addon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MenuManagementController extends Controller
{
    public function index()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->with(['variants', 'addons'])->ordered();
        }])->ordered()->get();

        $addons = Addon::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Menu/Index', [
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    // === CATEGORIES ===

    public function categories()
    {
        $categories = Category::with(['products' => function ($query) {
            $query->with(['variants', 'addons'])->ordered();
        }])->ordered()->get();

        $addons = Addon::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Menu/Index', [
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = Str::slug($validated['name']);
        $slugValidator = Validator::make(
            ['slug' => $slug],
            ['slug' => 'unique:categories,slug']
        );
        if ($slugValidator->fails()) {
            throw new ValidationException($slugValidator);
        }

        $validated['slug'] = $slug;
        $category = Category::create($validated);

        return back()->with('success', 'Kategoria została dodana');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        $slug = Str::slug($validated['name']);

        $slugValidator = Validator::make(
            ['slug' => $slug],
            ['slug' => 'unique:categories,slug,' . $category->id]
        );
        if ($slugValidator->fails()) {
            throw new ValidationException($slugValidator);
        }

        $validated['slug'] = $slug;

        $category->update($validated);

        return back()->with('success', 'Kategoria została zaktualizowana');
    }

    public function destroyCategory(Category $category)
    {
        // Sprawdź czy kategoria ma produkty
        if ($category->products()->count() > 0) {
            return back()->withErrors(['error' => 'Nie można usunąć kategorii, która ma przypisane produkty']);
        }

        $category->delete();

        return back()->with('success', 'Kategoria została usunięta');
    }

    // === PRODUCTS ===

    public function createProduct()
    {
        $categories = Category::active()->ordered()->get();
        $addons = Addon::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Menu/ProductForm', [
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'ingredients' => 'nullable|array',
            'allergens' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:wege,vegan,bezgluten,ostra',
            'is_featured' => 'boolean',
            'sort_order' => 'nullable|integer',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:addons,id',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            // Utwórz produkt
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? null,
                'ingredients' => $validated['ingredients'] ?? [],
                'allergens' => $validated['allergens'] ?? [],
                'tags' => $validated['tags'] ?? [],
                'is_featured' => $validated['is_featured'] ?? false,
                'is_available' => true,
                'sort_order' => $validated['sort_order'] ?? 0,
                'track_stock' => $validated['track_stock'] ?? false,
                'stock_quantity' => ($validated['track_stock'] ?? false) ? ($validated['stock_quantity'] ?? 0) : 0,
            ]);

            // Dodaj warianty
            foreach ($validated['variants'] as $index => $variant) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variant['name'],
                    'price' => $variant['price'],
                    'sort_order' => $index + 1,
                ]);
            }

            // Przypisz dodatki
            if (!empty($validated['addon_ids'])) {
                $product->addons()->attach($validated['addon_ids']);
            }
        });

        return redirect()->route('tenant.manager.menu.index')->with('success', 'Produkt został dodany');
    }

    public function editProduct(Product $product)
    {
        $product->load(['category', 'variants' => function ($q) {
            $q->ordered();
        }, 'addons']);

        $categories = Category::active()->ordered()->get();
        $addons = Addon::orderBy('name')->get();

        return Inertia::render('Tenant/Manager/Menu/ProductForm', [
            'product' => $product,
            'categories' => $categories,
            'addons' => $addons,
        ]);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'ingredients' => 'nullable|array',
            'allergens' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|in:wege,vegan,bezgluten,ostra',
            'is_featured' => 'boolean',
            'is_available' => 'boolean',
            'sort_order' => 'nullable|integer',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:addons,id',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($validated, $product) {
            // Zaktualizuj produkt
            $product->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'image' => $validated['image'] ?? $product->image,
                'ingredients' => $validated['ingredients'] ?? [],
                'allergens' => $validated['allergens'] ?? [],
                'tags' => $validated['tags'] ?? [],
                'is_featured' => $validated['is_featured'] ?? false,
                'is_available' => $validated['is_available'] ?? true,
                'sort_order' => $validated['sort_order'] ?? 0,
                'track_stock' => $validated['track_stock'] ?? false,
                'stock_quantity' => ($validated['track_stock'] ?? false) ? ($validated['stock_quantity'] ?? 0) : 0,
            ]);

            // Upsert wariantów: zachowaj istniejące ID (bezpieczne dla order_items),
            // aktualizuj dane, twórz nowe, usuwaj pominięte.
            $incomingIds = collect($validated['variants'])->pluck('id')->filter()->all();
            $product->variants()->whereNotIn('id', $incomingIds)->delete();

            foreach ($validated['variants'] as $index => $variantData) {
                if (!empty($variantData['id'])) {
                    ProductVariant::where('id', $variantData['id'])
                        ->where('product_id', $product->id)
                        ->update([
                            'name'       => $variantData['name'],
                            'price'      => $variantData['price'],
                            'sort_order' => $index + 1,
                        ]);
                } else {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'name'       => $variantData['name'],
                        'price'      => $variantData['price'],
                        'sort_order' => $index + 1,
                    ]);
                }
            }

            // Zaktualizuj dodatki
            $addonIds = $validated['addon_ids'] ?? [];
            $product->addons()->sync($addonIds);
        });

        return redirect()->route('tenant.manager.menu.index')->with('success', 'Produkt został zaktualizowany');
    }

    public function destroyProduct(Product $product)
    {
        DB::transaction(function () use ($product) {
            $product->variants()->delete();
            $product->addons()->detach();
            $product->delete();
        });

        return back()->with('success', 'Produkt został usunięty');
    }

    public function toggleAvailability(Product $product)
    {
        $product->update([
            'is_available' => !$product->is_available,
        ]);

        return back()->with('success', 'Status dostępności został zmieniony');
    }

    // === ADDONS ===

    public function storeAddon(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
        ]);

        Addon::create($validated);

        return back()->with('success', 'Dodatek został dodany');
    }

    public function destroyAddon(Addon $addon)
    {
        $addon->delete();

        return back()->with('success', 'Dodatek został usunięty');
    }

    // === CSV IMPORT ===

    public function importTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="menu_import_template.csv"',
        ];

        $rows = [
            ['kategoria', 'nazwa', 'opis', 'wariant', 'cena', 'polecany', 'dostepny', 'skladniki', 'alergeny', 'kolejnosc'],
            ['Pizza', 'Margherita', 'Klasyczna pizza z sosem pomidorowym i mozzarellą', 'Mała (25cm)', '29.90', '1', '1', 'sos pomidorowy, mozzarella, bazylia', 'gluten, nabiał', '1'],
            ['Pizza', 'Margherita', '', 'Duża (40cm)', '44.90', '', '', '', '', ''],
            ['Sałatki', 'Cezar', 'Sałatka cezar z grillowanym kurczakiem', 'Porcja', '22.50', '0', '1', 'sałata romana, kurczak, parmezan, croutons, sos cezar', 'gluten, nabiał, jajka', '1'],
        ];

        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\r\n";
        }

        return response($csv, 200, $headers);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // Detect and strip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            return back()->withErrors(['file' => 'Plik CSV jest pusty lub nieprawidłowy.']);
        }

        // Normalize header keys + tłumaczenie polskich nazw kolumn na angielskie klucze
        $columnMap = [
            'kategoria'  => 'category_name',
            'nazwa'      => 'product_name',
            'opis'       => 'description',
            'wariant'    => 'variant_name',
            'cena'       => 'price',
            'polecany'   => 'is_featured',
            'dostepny'   => 'is_available',
            'skladniki'  => 'ingredients',
            'alergeny'   => 'allergens',
            'kolejnosc'  => 'sort_order',
        ];
        $header = array_map(function ($h) use ($columnMap) {
            $h = trim(strtolower($h));
            return $columnMap[$h] ?? $h;
        }, $header);

        $required = ['category_name', 'product_name', 'variant_name', 'price'];
        foreach ($required as $col) {
            if (!in_array($col, $header)) {
                return back()->withErrors(['file' => "Brak wymaganej kolumny: {$col}"]);
            }
        }

        $imported = 0;
        $errors = [];
        $row = 1;

        DB::transaction(function () use ($handle, $header, &$imported, &$errors, &$row) {
            $categoryCache = [];
            $productCache  = [];

            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                if (count($data) < count($header)) continue;

                $record = array_combine($header, $data);
                $catName     = trim($record['category_name'] ?? '');
                $prodName    = trim($record['product_name'] ?? '');
                $variantName = trim($record['variant_name'] ?? '');
                $priceRaw    = trim($record['price'] ?? '');
                $desc        = trim($record['description'] ?? '');

                if (!$catName || !$prodName || !$variantName) {
                    $errors[] = "Wiersz {$row}: brakujące wymagane pola.";
                    continue;
                }

                $price = (float) str_replace(',', '.', $priceRaw);
                if ($price <= 0) {
                    $errors[] = "Wiersz {$row}: nieprawidłowa cena '{$priceRaw}'.";
                    continue;
                }

                // Optional fields
                $isFeatured  = isset($record['is_featured'])  && in_array(trim($record['is_featured']), ['1', 'tak', 'yes', 'true']);
                $isAvailable = !isset($record['is_available']) || trim($record['is_available']) === '' || in_array(trim($record['is_available']), ['1', 'tak', 'yes', 'true']);
                $sortOrder   = isset($record['sort_order']) && is_numeric(trim($record['sort_order'])) ? (int) trim($record['sort_order']) : 0;

                // Ingredients: comma-separated string → array
                $ingredients = [];
                if (!empty($record['ingredients'])) {
                    $ingredients = array_map('trim', explode(',', $record['ingredients']));
                    $ingredients = array_filter($ingredients);
                    $ingredients = array_values($ingredients);
                }

                // Allergens: comma-separated string → array
                $allergens = [];
                if (!empty($record['allergens'])) {
                    $allergens = array_map('trim', explode(',', $record['allergens']));
                    $allergens = array_filter($allergens);
                    $allergens = array_values($allergens);
                }

                // Find or create category
                if (!isset($categoryCache[$catName])) {
                    $slug = Str::slug($catName);
                    $cat  = Category::firstOrCreate(
                        ['slug' => $slug],
                        ['name' => $catName, 'slug' => $slug, 'sort_order' => 0]
                    );
                    $categoryCache[$catName] = $cat->id;
                }
                $categoryId = $categoryCache[$catName];

                // Find or create product (key: category + name)
                $productKey = $categoryId . '|' . $prodName;
                if (!isset($productCache[$productKey])) {
                    $slug = Str::slug($prodName);
                    $base = $slug;
                    $n = 1;
                    while (Product::where('slug', $slug)->exists()) {
                        $slug = $base . '-' . $n++;
                    }
                    $prod = Product::firstOrCreate(
                        ['category_id' => $categoryId, 'name' => $prodName],
                        [
                            'slug'         => $slug,
                            'description'  => $desc ?: null,
                            'is_available' => $isAvailable,
                            'is_featured'  => $isFeatured,
                            'ingredients'  => $ingredients ?: null,
                            'allergens'    => $allergens ?: null,
                            'sort_order'   => $sortOrder,
                        ]
                    );
                    $productCache[$productKey] = $prod->id;
                }
                $productId = $productCache[$productKey];

                // Add variant (skip if same name already exists)
                $exists = ProductVariant::where('product_id', $productId)
                    ->where('name', $variantName)
                    ->exists();
                if (!$exists) {
                    ProductVariant::create([
                        'product_id' => $productId,
                        'name'       => $variantName,
                        'price'      => $price,
                        'sort_order' => 0,
                    ]);
                    $imported++;
                }
            }
        });

        fclose($handle);

        $msg = "Zaimportowano {$imported} wariantów produktów.";
        if ($errors) {
            $msg .= ' Błędy: ' . implode('; ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $msg);
    }
}
