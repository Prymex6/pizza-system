<?php

namespace App\Http\Controllers;

use App\Models\Landlord\Plan;
use Illuminate\Http\Response;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'price', 'features']);

        return Inertia::render('Landing', [
            'plans' => $plans,
        ]);
    }

    public function sitemap(): Response
    {
        $base = rtrim(config('app.url'), '/');
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $xml .= $this->url($base . '/',        now(),             'weekly', '1.0');
        $xml .= $this->url($base . '/#cennik', now()->subDays(1), 'monthly', '0.8');
        $xml .= $this->url($base . '/#funkcje', now()->subDays(1), 'monthly', '0.7');
        $xml .= $this->url($base . '/#kontakt', now()->subDays(1), 'monthly', '0.6');
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $content = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /landlord/\n"
            . "Disallow: /landlord-login\n"
            . "\n"
            . 'Sitemap: ' . rtrim(config('app.url'), '/') . "/sitemap.xml\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }

    private function url(string $loc, $lastmod, string $changefreq, string $priority): string
    {
        return '<url>'
            . '<loc>' . htmlspecialchars($loc) . '</loc>'
            . '<lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>'
            . '<changefreq>' . $changefreq . '</changefreq>'
            . '<priority>' . $priority . '</priority>'
            . '</url>';
    }
}
