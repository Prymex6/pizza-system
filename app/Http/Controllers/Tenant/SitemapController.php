<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate sitemap.xml for tenant
     */
    public function index(): Response
    {
        $domain = request()->getHost();
        $baseUrl = request()->getScheme() . '://' . $domain;

        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Only the menu homepage — products/categories have no dedicated URLs (SPA)
        $sitemap .= $this->addUrl($baseUrl, now(), 'daily', '1.0');

        $sitemap .= '</urlset>';

        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate robots.txt for tenant
     */
    public function robots(): Response
    {
        $domain = request()->getHost();
        $baseUrl = request()->getScheme() . '://' . $domain;

        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "Disallow: /manager/\n";
        $robots .= "Disallow: /staff/\n";
        $robots .= "Disallow: /checkout\n";
        $robots .= "Disallow: /order/\n";
        $robots .= "Disallow: /payment/\n";
        $robots .= "\n";
        $robots .= "Sitemap: {$baseUrl}/sitemap.xml\n";

        return response($robots, 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Helper method to create URL entry
     */
    protected function addUrl(string $url, $lastmod, string $changefreq, string $priority): string
    {
        $xml = '<url>';
        $xml .= '<loc>' . htmlspecialchars($url) . '</loc>';
        $xml .= '<lastmod>' . $lastmod->format('Y-m-d') . '</lastmod>';
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';

        return $xml;
    }
}
