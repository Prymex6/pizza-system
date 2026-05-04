<?php

namespace Database\Seeders;

use App\Models\Tenant\Addon;
use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Table;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('⛔ TenantSeeder zawiera dane demonstracyjne i NIE powinien być uruchamiany w środowisku produkcyjnym!');
            $this->command->warn('   Aby kontynuować w produkcji, usuń ten guard lub użyj dedykowanego seedera produkcyjnego.');

            if (!$this->command->confirm('Czy na pewno chcesz zasiać dane demo w produkcji?', false)) {
                $this->command->info('Anulowano.');
                return;
            }
        }

        $this->call(TenantSettingsSeeder::class);
        $this->call(DeliveryZonesSeeder::class);

        $this->seedStaff();
        $this->seedCustomers();
        $this->seedTables();
        $this->seedMenu();
        $this->seedDiscountCodes();

        $this->command->info('  ✔ Dane demonstracyjne tenanta gotowe.');
    }

    // ── Staff ─────────────────────────────────────────────────────────────────

    private function seedStaff(): void
    {
        $staff = [
            [
                'name'      => 'Jan Kowalski',
                'email'     => 'manager@example.com',
                'password'  => Hash::make('password'),
                'role'      => 'manager',
                'phone'     => '+48 500 100 200',
                'is_active' => true,
            ],
            [
                'name'      => 'Piotr Nowak',
                'email'     => 'chef@example.com',
                'password'  => Hash::make('password'),
                'role'      => 'chef',
                'phone'     => '+48 500 100 201',
                'is_active' => true,
            ],
            [
                'name'      => 'Anna Wiśniewska',
                'email'     => 'waiter@example.com',
                'password'  => Hash::make('password'),
                'role'      => 'waiter',
                'phone'     => '+48 500 100 202',
                'is_active' => true,
            ],
            [
                'name'      => 'Marek Zielony',
                'email'     => 'driver@example.com',
                'password'  => Hash::make('password'),
                'role'      => 'driver',
                'phone'     => '+48 500 100 203',
                'is_active' => true,
            ],
        ];

        foreach ($staff as $member) {
            User::updateOrCreate(['email' => $member['email']], $member);
        }

        $this->command->line('  📋 Staff: ' . count($staff) . ' kont.');
    }

    // ── Demo customers ────────────────────────────────────────────────────────

    private function seedCustomers(): void
    {
        $customers = [
            [
                'name'           => 'Tomasz Testowy',
                'email'          => 'klient@example.pl',
                'password'       => Hash::make('password'),
                'phone'          => '+48 600 123 456',
                'delivery_city'        => 'Kraków',
                'delivery_address'     => 'ul. Długa 5',
                'delivery_postal_code' => '31-147',
                'loyalty_points'       => 150,
                'loyalty_tier'         => 'silver',
            ],
            [
                'name'           => 'Maria Przykładowa',
                'email'          => 'maria@example.pl',
                'password'       => Hash::make('password'),
                'phone'          => '+48 601 234 567',
                'delivery_city'        => 'Kraków',
                'delivery_address'     => 'ul. Grodzka 10',
                'delivery_postal_code' => '31-006',
                'loyalty_points' => 45,
                'loyalty_tier'   => 'bronze',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }

        $this->command->line('  👤 Klienci: ' . count($customers) . ' kont.');
    }

    // ── Tables ────────────────────────────────────────────────────────────────

    private function seedTables(): void
    {
        $tables = [
            ['number' => 1,  'seats' => 2, 'location' => 'Sala główna'],
            ['number' => 2,  'seats' => 2, 'location' => 'Sala główna'],
            ['number' => 3,  'seats' => 4, 'location' => 'Sala główna'],
            ['number' => 4,  'seats' => 4, 'location' => 'Sala główna'],
            ['number' => 5,  'seats' => 6, 'location' => 'Sala główna'],
            ['number' => 6,  'seats' => 6, 'location' => 'Sala główna'],
            ['number' => 7,  'seats' => 8, 'location' => 'Sala główna'],
            ['number' => 8,  'seats' => 4, 'location' => 'Ogródek'],
            ['number' => 9,  'seats' => 4, 'location' => 'Ogródek'],
            ['number' => 10, 'seats' => 6, 'location' => 'Ogródek'],
        ];

        foreach ($tables as $table) {
            Table::updateOrCreate(
                ['number' => $table['number']],
                array_merge($table, ['is_active' => true])
            );
        }

        $this->command->line('  🪑 Stoliki: ' . count($tables) . ' szt.');
    }

    // ── Menu ──────────────────────────────────────────────────────────────────

    private function seedMenu(): void
    {
        // Kategorie
        $pizzaCategory = Category::updateOrCreate(
            ['slug' => 'pizze'],
            ['name' => 'Pizze', 'icon' => '🍕', 'sort_order' => 1, 'is_active' => true]
        );
        $burgerCategory = Category::updateOrCreate(
            ['slug' => 'burgery'],
            ['name' => 'Burgery', 'icon' => '🍔', 'sort_order' => 2, 'is_active' => true]
        );
        $pastaCategory = Category::updateOrCreate(
            ['slug' => 'makarony'],
            ['name' => 'Makarony', 'icon' => '🍝', 'sort_order' => 3, 'is_active' => true]
        );
        $saladsCategory = Category::updateOrCreate(
            ['slug' => 'salatki'],
            ['name' => 'Sałatki', 'icon' => '🥗', 'sort_order' => 4, 'is_active' => true]
        );
        $drinksCategory = Category::updateOrCreate(
            ['slug' => 'napoje'],
            ['name' => 'Napoje', 'icon' => '🥤', 'sort_order' => 5, 'is_active' => true]
        );
        $dessertsCategory = Category::updateOrCreate(
            ['slug' => 'desery'],
            ['name' => 'Desery', 'icon' => '🍰', 'sort_order' => 6, 'is_active' => true]
        );

        // Dodatki
        $addonData = [
            ['name' => 'Extra ser',      'price' => 4.00],
            ['name' => 'Pieczarki',      'price' => 3.50],
            ['name' => 'Szynka',         'price' => 5.00],
            ['name' => 'Salami',         'price' => 5.00],
            ['name' => 'Boczek',         'price' => 5.50],
            ['name' => 'Cebula',         'price' => 2.00],
            ['name' => 'Papryka',        'price' => 3.00],
            ['name' => 'Oliwki',         'price' => 3.50],
            ['name' => 'Kukurydza',      'price' => 2.50],
            ['name' => 'Ananas',         'price' => 3.00],
            ['name' => 'Podwójne mięso', 'price' => 8.00],
            ['name' => 'Sos czosnkowy',  'price' => 2.00],
            ['name' => 'Jalapeño',       'price' => 2.50],
            ['name' => 'Rukola',         'price' => 3.00],
        ];

        $addons = collect($addonData)->map(fn ($a) =>
            Addon::updateOrCreate(['name' => $a['name']], array_merge($a, ['is_available' => true]))
        );

        // ── Pizze ──
        $pizzas = [
            [
                'name'        => 'Margherita',
                'slug'        => 'margherita',
                'description' => 'Klasyczna pizza z sosem pomidorowym i mozzarellą',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Bazylia', 'Oliwa z oliwek'],
                'allergens'   => ['Gluten', 'Mleko'],
                'is_featured' => true,
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 25.00],
                    ['name' => 'Średnia (40 cm)','price' => 35.00],
                    ['name' => 'Duża (50 cm)',  'price' => 45.00],
                ],
            ],
            [
                'name'        => 'Pepperoni',
                'slug'        => 'pepperoni',
                'description' => 'Pikantna pizza z salami pepperoni i ostrą papryką',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Salami pepperoni'],
                'allergens'   => ['Gluten', 'Mleko'],
                'is_featured' => true,
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 28.00],
                    ['name' => 'Średnia (40 cm)','price' => 38.00],
                    ['name' => 'Duża (50 cm)',  'price' => 48.00],
                ],
            ],
            [
                'name'        => 'Capricciosa',
                'slug'        => 'capricciosa',
                'description' => 'Pizza z szynką i pieczarkami – włoski klasyk',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Szynka', 'Pieczarki'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 30.00],
                    ['name' => 'Średnia (40 cm)','price' => 40.00],
                    ['name' => 'Duża (50 cm)',  'price' => 50.00],
                ],
            ],
            [
                'name'        => 'Quattro Formaggi',
                'slug'        => 'quattro-formaggi',
                'description' => 'Pizza z czterema rodzajami sera dla prawdziwych smakoszy',
                'ingredients' => ['Sos śmietanowy', 'Mozzarella', 'Gorgonzola', 'Parmezan', 'Ser kozi'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 32.00],
                    ['name' => 'Średnia (40 cm)','price' => 42.00],
                    ['name' => 'Duża (50 cm)',  'price' => 52.00],
                ],
            ],
            [
                'name'        => 'Hawajska',
                'slug'        => 'hawajska',
                'description' => 'Kontrowersyjna, ale lubiana – szynka i ananas',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Szynka', 'Ananas'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 29.00],
                    ['name' => 'Średnia (40 cm)','price' => 39.00],
                    ['name' => 'Duża (50 cm)',  'price' => 49.00],
                ],
            ],
            [
                'name'        => 'Wiejska',
                'slug'        => 'wiejska',
                'description' => 'Syta pizza z boczkiem, cebulą i papryką',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Boczek', 'Cebula', 'Papryka', 'Kukurydza'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 31.00],
                    ['name' => 'Średnia (40 cm)','price' => 41.00],
                    ['name' => 'Duża (50 cm)',  'price' => 51.00],
                ],
            ],
            [
                'name'        => 'Diavola',
                'slug'        => 'diavola',
                'description' => 'Ognista pizza dla lubiących ostre smaki',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Salami', 'Jalapeño', 'Chili'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 30.00],
                    ['name' => 'Średnia (40 cm)','price' => 40.00],
                    ['name' => 'Duża (50 cm)',  'price' => 50.00],
                ],
            ],
            [
                'name'        => 'Prosciutto e Rucola',
                'slug'        => 'prosciutto-rucola',
                'description' => 'Elegancka pizza z szynką parmeńską i rukolą',
                'ingredients' => ['Sos pomidorowy', 'Mozzarella', 'Szynka parmeńska', 'Rukola', 'Parmezan'],
                'allergens'   => ['Gluten', 'Mleko'],
                'is_featured' => true,
                'variants'    => [
                    ['name' => 'Mała (30 cm)',  'price' => 35.00],
                    ['name' => 'Średnia (40 cm)','price' => 46.00],
                    ['name' => 'Duża (50 cm)',  'price' => 57.00],
                ],
            ],
        ];

        foreach ($pizzas as $index => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $pizzaCategory->id,
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ])
            );

            // Sync variants
            foreach ($variants as $vi => $variantData) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $variantData['name']],
                    array_merge($variantData, ['sort_order' => $vi + 1])
                );
            }

            $product->addons()->sync($addons->pluck('id'));
        }

        // ── Burgery ──
        $burgers = [
            [
                'name'        => 'Classic Burger',
                'slug'        => 'classic-burger',
                'description' => 'Klasyczny burger wołowy z sałatą, pomidorem i sosem własnym',
                'ingredients' => ['Bułka', 'Wołowina 200g', 'Sałata', 'Pomidor', 'Cebula', 'Sos klasyczny'],
                'allergens'   => ['Gluten', 'Jaja'],
                'variants'    => [
                    ['name' => 'Standard', 'price' => 22.00],
                    ['name' => 'Double',   'price' => 32.00],
                ],
            ],
            [
                'name'        => 'Chicken Burger',
                'slug'        => 'chicken-burger',
                'description' => 'Soczysty kurczak panierowany z majonezem i świeżymi warzywami',
                'ingredients' => ['Bułka', 'Kurczak panierowany', 'Sałata', 'Pomidor', 'Majonez'],
                'allergens'   => ['Gluten', 'Jaja'],
                'variants'    => [
                    ['name' => 'Standard', 'price' => 20.00],
                    ['name' => 'Double',   'price' => 28.00],
                ],
            ],
            [
                'name'        => 'BBQ Burger',
                'slug'        => 'bbq-burger',
                'description' => 'Wołowina z sosem BBQ, boczkiem i karamelizowaną cebulą',
                'ingredients' => ['Bułka', 'Wołowina 200g', 'Boczek', 'Cebula karmelizowana', 'Sos BBQ', 'Cheddar'],
                'allergens'   => ['Gluten', 'Mleko'],
                'is_featured' => true,
                'variants'    => [
                    ['name' => 'Standard', 'price' => 26.00],
                    ['name' => 'Double',   'price' => 36.00],
                ],
            ],
        ];

        $burgerAddonIds = $addons->whereIn('name', ['Podwójne mięso', 'Boczek', 'Sos czosnkowy', 'Cebula', 'Jalapeño'])->pluck('id');

        foreach ($burgers as $index => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $burgerCategory->id,
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ])
            );

            foreach ($variants as $vi => $variantData) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $variantData['name']],
                    array_merge($variantData, ['sort_order' => $vi + 1])
                );
            }

            $product->addons()->sync($burgerAddonIds);
        }

        // ── Makarony ──
        $pastas = [
            [
                'name'        => 'Spaghetti Bolognese',
                'slug'        => 'spaghetti-bolognese',
                'description' => 'Klasyczny makaron z sosem bolońskim z wołowiny i warzyw',
                'ingredients' => ['Spaghetti', 'Mięso wołowe', 'Pomidory', 'Cebula', 'Marchew', 'Parmezan'],
                'allergens'   => ['Gluten', 'Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 24.00]],
            ],
            [
                'name'        => 'Penne Carbonara',
                'slug'        => 'penne-carbonara',
                'description' => 'Kremowy makaron z boczkiem i parmezanem',
                'ingredients' => ['Penne', 'Boczek', 'Jajka', 'Parmezan', 'Śmietana 30%'],
                'allergens'   => ['Gluten', 'Jaja', 'Mleko'],
                'is_featured' => true,
                'variants'    => [['name' => 'Porcja', 'price' => 26.00]],
            ],
            [
                'name'        => 'Tagliatelle z łososiem',
                'slug'        => 'tagliatelle-losos',
                'description' => 'Delikatny makaron z łososiem w sosie śmietanowym z koperkiem',
                'ingredients' => ['Tagliatelle', 'Łosoś', 'Śmietana', 'Koperek', 'Cytryna'],
                'allergens'   => ['Gluten', 'Mleko', 'Ryby'],
                'variants'    => [['name' => 'Porcja', 'price' => 32.00]],
            ],
        ];

        foreach ($pastas as $index => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $pastaCategory->id,
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ])
            );

            foreach ($variants as $vi => $variantData) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $variantData['name']],
                    array_merge($variantData, ['sort_order' => $vi + 1])
                );
            }
        }

        // ── Sałatki ──
        $salads = [
            [
                'name'        => 'Sałatka Caesar',
                'slug'        => 'salatka-caesar',
                'description' => 'Klasyczna sałatka z kurczakiem, grzankami i sosem Caesar',
                'ingredients' => ['Sałata Cos', 'Kurczak grillowany', 'Grzanki', 'Sos Caesar', 'Parmezan'],
                'allergens'   => ['Gluten', 'Jaja', 'Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 22.00]],
            ],
            [
                'name'        => 'Sałatka Caprese',
                'slug'        => 'salatka-caprese',
                'description' => 'Pomidory, mozzarella i bazylia z oliwą i balsamico',
                'ingredients' => ['Pomidory', 'Mozzarella buffalo', 'Bazylia', 'Oliwa', 'Balsamico'],
                'allergens'   => ['Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 24.00]],
            ],
        ];

        foreach ($salads as $index => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $saladsCategory->id,
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ])
            );

            foreach ($variants as $vi => $variantData) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $variantData['name']],
                    array_merge($variantData, ['sort_order' => $vi + 1])
                );
            }
        }

        // ── Napoje ──
        $drinks = [
            ['name' => 'Coca-Cola 0,5 l',         'price' => 8.00],
            ['name' => 'Coca-Cola Zero 0,5 l',     'price' => 8.00],
            ['name' => 'Fanta pomarańczowa 0,5 l', 'price' => 8.00],
            ['name' => 'Sprite 0,5 l',             'price' => 8.00],
            ['name' => 'Woda mineralna 0,5 l',     'price' => 5.00],
            ['name' => 'Woda gazowana 0,5 l',      'price' => 5.00],
            ['name' => 'Sok pomarańczowy 0,3 l',   'price' => 7.00],
            ['name' => 'Sok jabłkowy 0,3 l',       'price' => 7.00],
            ['name' => 'Piwo Tyskie 0,5 l',        'price' => 10.00],
            ['name' => 'Piwo bezalkoholowe 0,5 l', 'price' => 9.00],
            ['name' => 'Kawa espresso',             'price' => 8.00],
            ['name' => 'Kawa americano',            'price' => 9.00],
            ['name' => 'Herbata 0,3 l',            'price' => 7.00],
        ];

        foreach ($drinks as $index => $drink) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($drink['name'])],
                [
                    'category_id' => $drinksCategory->id,
                    'name'        => $drink['name'],
                    'slug'        => Str::slug($drink['name']),
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ]
            );

            ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'name' => 'Standard'],
                ['price' => $drink['price'], 'sort_order' => 1]
            );
        }

        // ── Desery ──
        $desserts = [
            [
                'name'        => 'Tiramisu',
                'slug'        => 'tiramisu',
                'description' => 'Klasyczny włoski deser z mascarpone i espresso',
                'ingredients' => ['Mascarpone', 'Biszkopty', 'Espresso', 'Kakao', 'Jajka'],
                'allergens'   => ['Gluten', 'Jaja', 'Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 16.00]],
            ],
            [
                'name'        => 'Sernik włoski',
                'slug'        => 'sernik-wloski',
                'description' => 'Kremowy sernik na zimno z owocami sezonowymi',
                'ingredients' => ['Ricotta', 'Śmietana', 'Cukier', 'Owoce sezonowe'],
                'allergens'   => ['Jaja', 'Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 14.00]],
            ],
            [
                'name'        => 'Panna cotta',
                'slug'        => 'panna-cotta',
                'description' => 'Włoski deser śmietankowy z sosem truskawkowym',
                'ingredients' => ['Śmietana', 'Cukier', 'Żelatyna', 'Sos truskawkowy'],
                'allergens'   => ['Mleko'],
                'variants'    => [['name' => 'Porcja', 'price' => 13.00]],
            ],
        ];

        foreach ($desserts as $index => $data) {
            $variants = $data['variants'];
            unset($data['variants']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'category_id' => $dessertsCategory->id,
                    'sort_order'  => $index + 1,
                    'is_available'=> true,
                ])
            );

            foreach ($variants as $vi => $variantData) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'name' => $variantData['name']],
                    array_merge($variantData, ['sort_order' => $vi + 1])
                );
            }
        }

        $this->command->line('  🍕 Menu: kategorie, produkty i warianty gotowe.');
    }

    // ── Discount codes ────────────────────────────────────────────────────────

    private function seedDiscountCodes(): void
    {
        $codes = [
            [
                'code'            => 'WELCOME10',
                'type'            => 'percentage',
                'value'           => 10.00,
                'min_order_value' => 30.00,
                'max_uses'        => null,
                'is_active'       => true,
            ],
            [
                'code'            => 'DARMOWA5',
                'type'            => 'fixed',
                'value'           => 5.00,
                'min_order_value' => 50.00,
                'max_uses'        => 100,
                'is_active'       => true,
            ],
            [
                'code'            => 'PIZZA20',
                'type'            => 'percentage',
                'value'           => 20.00,
                'min_order_value' => 60.00,
                'max_uses'        => 50,
                'valid_until'     => now()->addDays(30),
                'is_active'       => true,
            ],
            [
                'code'            => 'STARY',
                'type'            => 'percentage',
                'value'           => 15.00,
                'min_order_value' => 0,
                'max_uses'        => 0,
                'used_count'      => 0,
                'is_active'       => false,
            ],
        ];

        foreach ($codes as $code) {
            DiscountCode::updateOrCreate(['code' => $code['code']], $code);
        }

        $this->command->line('  🏷️  Kody rabatowe: ' . count($codes) . ' szt.');
    }
}
