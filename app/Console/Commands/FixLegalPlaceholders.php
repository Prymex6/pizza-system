<?php

namespace App\Console\Commands;

use App\Models\Tenant\Setting;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class FixLegalPlaceholders extends Command
{
    protected $signature   = 'tenant:fix-legal-placeholders';
    protected $description = 'Zamień hardkodowaną nazwę restauracji na {restaurant_name} w regulaminie i polityce prywatności';

    public function handle(): int
    {
        $tenants = \App\Models\Landlord\Tenant::all();

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant);

            foreach (['terms_content', 'privacy_content'] as $key) {
                $content = Setting::get($key, '');

                if (!$content) {
                    continue;
                }

                if (str_contains($content, '{restaurant_name}')) {
                    $this->line("  [{$tenant->id}] {$key} — już ma placeholder, pomijam");
                    continue;
                }

                // Zamień "Nazwa handlowa: cokolwiek" na placeholder
                $fixed = preg_replace(
                    '/Nazwa handlowa: [^<\n]+/',
                    'Nazwa handlowa: {restaurant_name}',
                    $content
                );

                if ($fixed !== $content) {
                    Setting::set($key, $fixed, 'text');
                    $this->info("  [{$tenant->id}] {$key} — naprawiono");
                } else {
                    $this->warn("  [{$tenant->id}] {$key} — nie znaleziono 'Nazwa handlowa:', pomijam");
                }
            }

            tenancy()->end();
        }

        $this->info('Gotowe.');
        return self::SUCCESS;
    }
}
