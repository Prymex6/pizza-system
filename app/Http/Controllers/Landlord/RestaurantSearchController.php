<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\RestaurantLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RestaurantSearchController extends Controller
{
    public function index()
    {
        return Inertia::render('Landlord/RestaurantSearch');
    }

    public function findContact(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $query = trim($request->name . ' ' . $request->city . ' restauracja');

        $body = null;

        // 1. Try Startpage (returns real Google results, no bot blocking)
        try {
            $sp = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
            ])->timeout(12)->get('https://www.startpage.com/search', ['q' => $query, 'cat' => 'web', 'language' => 'pl']);

            if ($sp->successful() && strlen($sp->body()) > 5000) {
                $body = $sp->body();
            }
        } catch (\Exception) {}

        // 2. Fallback: Brave Search
        if (!$body) {
            try {
                $brave = Http::withHeaders([
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
                ])->timeout(12)->get('https://search.brave.com/search', ['q' => $query]);

                if ($brave->successful()) {
                    $body = $brave->body();
                }
            } catch (\Exception) {}
        }

        if (!$body) {
            return response()->json(['website' => null, 'facebook' => null, 'email' => null, 'urls' => []]);
        }

        // Extract plain <a href> URLs
        $urls = [];
        preg_match_all('/<a[^>]+href="(https?:\/\/[^"]+)"/i', $body, $m);
        foreach ($m[1] ?? [] as $u) {
            // Decode HTML entities (e.g. &amp; in Play Store links)
            $u = html_entity_decode($u, ENT_QUOTES);
            if (strlen($u) > 15 && strlen($u) < 300) {
                $urls[] = $u;
            }
        }

        $skipDomains = ['startpage.com', 'ixquick.com', 'brave.com', 'duckduckgo.com',
                        'w3.org', 'schema.org', 'apple.com', 'mozilla.org', 'microsoft.com',
                        'google.com/search', 'google.com/maps/place'];
        $urls = array_unique(array_values(array_filter($urls, function ($u) use ($skipDomains) {
            foreach ($skipDomains as $s) {
                if (str_contains($u, $s)) return false;
            }
            return true;
        })));

        $website  = null;
        $facebook = null;
        $email    = null;

        foreach ($urls as $u) {
            // Facebook
            if (!$facebook && preg_match('/facebook\.com\/[a-zA-Z0-9\.\-\_\/]{3,}/i', $u)) {
                $facebook = preg_replace('/[?&](fbclid|__cft__|__tn__)=[^&"]*/', '', $u);
                $facebook = rtrim($facebook, '?&');
            }
        }

        // Website: first non-directory, non-social URL
        $dirPattern = '/pyszne|ubereats|wolt|glovo|tripadvisor|zomato|gastronauci|yelp|google|facebook|instagram|twitter|youtube|duckduckgo|brave/i';
        foreach ($urls as $u) {
            if (!$website && !preg_match($dirPattern, $u)) {
                $website = $u;
                break;
            }
        }

        // Extract emails
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6}/', $body, $em);
        $emailSkip = ['@duckduckgo', '@brave', '@w3', '@schema', '@example'];
        foreach ($em[0] ?? [] as $e) {
            $ok = true;
            foreach ($emailSkip as $s) { if (str_contains($e, $s)) $ok = false; }
            if ($ok) { $email = $e; break; }
        }

        return response()->json([
            'website'  => $website,
            'facebook' => $facebook,
            'email'    => $email,
            'urls'     => array_slice($urls, 0, 8),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'city'   => ['required', 'string', 'max:100'],
            'radius' => ['required', 'integer', 'in:1000,2000,5000,10000,20000,50000'],
        ]);

        $city   = $request->city;
        $radius = (int) $request->radius;

        // 1. Geocode city → lat/lng via Nominatim
        $geo = Http::withHeaders([
                'User-Agent' => config('app.name', 'App') . '/1.0 (' . env('MAIL_FROM_ADDRESS', 'contact@example.com') . ')',
                'Accept'     => 'application/json',
            ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q'              => $city,
                'format'         => 'json',
                'limit'          => 1,
                'addressdetails' => 1,
            ]);

        if ($geo->failed() || empty($geo->json())) {
            return response()->json(['error' => 'Nie znaleziono miasta „' . $city . '". Sprawdź pisownię.'], 422);
        }

        $place = $geo->json()[0];
        $lat   = (float) $place['lat'];
        $lon   = (float) $place['lon'];

        // 2. Overpass API — restaurants in radius
        // Use nwr (node+way+relation) for better coverage
        $overpassQuery = "[out:json][timeout:30];nwr[\"amenity\"~\"restaurant|cafe|fast_food\"](around:{$radius},{$lat},{$lon});out center tags;";

        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://overpass.openstreetmap.ru/api/interpreter',
            'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
        ];

        $overpass = null;
        $lastError = null;
        foreach ($endpoints as $url) {
            try {
                $resp = Http::withHeaders(['User-Agent' => '{{ config('app.name', 'Roveto') }}/1.0 ({{ env('MAIL_FROM_ADDRESS', 'contact@example.com') }})'])
                    ->timeout(35)
                    ->asForm()
                    ->post($url, ['data' => $overpassQuery]);
                if ($resp->successful() && !empty($resp->json('elements'))) {
                    $overpass = $resp;
                    break;
                }
                $lastError = 'HTTP ' . $resp->status();
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }

        if (!$overpass) {
            return response()->json(['error' => 'Nie udało się pobrać danych z OpenStreetMap. Spróbuj ponownie za chwilę lub zmień promień wyszukiwania.'], 503);
        }

        $elements = $overpass->json('elements') ?? [];

        $results = collect($elements)
            ->filter(fn($el) => !empty($el['tags']['name']) && strlen($el['tags']['name']) > 2)
            ->map(function ($el) {
                $tags = $el['tags'];

                $website  = $tags['website'] ?? $tags['contact:website'] ?? $tags['url'] ?? null;
                $facebook = $tags['contact:facebook'] ?? $tags['facebook'] ?? null;
                $email    = $tags['email'] ?? $tags['contact:email'] ?? null;
                $phone    = $tags['phone'] ?? $tags['contact:phone'] ?? null;

                // Normalize Facebook URL
                if ($facebook && !str_starts_with($facebook, 'http')) {
                    $facebook = 'https://facebook.com/' . ltrim($facebook, '/');
                }

                $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
                $lon = $el['lon'] ?? $el['center']['lon'] ?? null;

                return [
                    'id'       => $el['id'],
                    'name'     => $tags['name'],
                    'type'     => match($tags['amenity'] ?? '') {
                        'cafe'      => 'Kawiarnia',
                        'fast_food' => 'Fast food',
                        default     => 'Restauracja',
                    },
                    'address'  => trim(implode(', ', array_filter([
                        $tags['addr:street'] ?? null,
                        $tags['addr:housenumber'] ?? null,
                        $tags['addr:city'] ?? null,
                    ]))),
                    'website'  => $website,
                    'facebook' => $facebook,
                    'email'    => $email,
                    'phone'    => $phone,
                    'osm_url'  => "https://www.openstreetmap.org/{$el['type']}/{$el['id']}",
                    'maps_url' => $lat ? "https://www.google.com/maps?q={$lat},{$lon}" : null,
                    'has_contact' => $website || $facebook || $email || $phone,
                ];
            })
            ->values();

        // Upsert all found restaurants, attach contacted_at from DB
        $osmIds = $results->pluck('id')->all();
        $leads  = RestaurantLead::whereIn('osm_id', $osmIds)
            ->get()
            ->keyBy('osm_id');

        $upsertData = $results->map(fn($r) => [
            'osm_id'   => $r['id'],
            'name'     => $r['name'],
            'type'     => $r['type'],
            'address'  => $r['address'] ?: null,
            'city'     => $city,
            'website'  => $r['website'],
            'facebook' => $r['facebook'],
            'email'    => $r['email'],
            'phone'    => $r['phone'],
            'maps_url' => $r['maps_url'],
        ])->all();

        RestaurantLead::upsert(
            $upsertData,
            ['osm_id'],
            ['name', 'type', 'address', 'city', 'website', 'facebook', 'email', 'phone', 'maps_url']
        );

        $results = $results->map(function ($r) use ($leads) {
            $lead = $leads->get($r['id']);
            $r['contacted_at'] = $lead?->contacted_at?->toIso8601String();
            return $r;
        });

        return response()->json([
            'city'    => $place['display_name'],
            'lat'     => $lat,
            'lon'     => $lon,
            'count'   => $results->count(),
            'results' => $results,
        ]);
    }

    public function toggleContacted(Request $request)
    {
        $request->validate(['osm_id' => ['required', 'integer']]);

        $lead = RestaurantLead::where('osm_id', $request->osm_id)->firstOrFail();

        $lead->contacted_at = $lead->contacted_at ? null : now();
        $lead->save();

        return response()->json(['contacted_at' => $lead->contacted_at?->toIso8601String()]);
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'osm_id'  => ['required', 'integer'],
            'email'   => ['required', 'email'],
            'name'    => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $name    = $request->name;
        $email   = $request->email;
        $message = $request->message;

        Mail::send([], [], function ($mail) use ($name, $email, $message) {
            $mail->to($email, $name)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject('Propozycja współpracy – Roveto')
                ->text($message);
        });

        // Mark as contacted
        $lead = RestaurantLead::where('osm_id', $request->osm_id)->first();
        if ($lead && !$lead->contacted_at) {
            $lead->contacted_at = now();
            $lead->save();
        }

        return response()->json(['success' => true]);
    }
}
