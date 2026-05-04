<?php

namespace App\Services;

use App\Models\Tenant\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleReviewsService
{
    protected ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.api_key');
    }

    /**
     * Get reviews for the tenant's Google Place.
     * Results are cached for 1 hour to avoid excessive API calls.
     */
    public function getReviews(int $limit = 5): array
    {
        $placeId = Setting::get('google_place_id');

        if (!$placeId || !$this->apiKey) {
            return [
                'reviews' => [],
                'rating' => null,
                'total_reviews' => 0,
            ];
        }

        $cacheKey = 'google_reviews_' . md5($placeId);

        try {
            return Cache::remember($cacheKey, now()->addHour(), function () use ($placeId, $limit) {
                return $this->fetchFromGoogle($placeId, $limit);
            });
        } catch (\BadMethodCallException $e) {
            return $this->fetchFromGoogle($placeId, $limit);
        }
    }

    /**
     * Fetch place details (reviews) from Google Places API.
     */
    protected function fetchFromGoogle(string $placeId, int $limit): array
    {
        try {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'reviews,rating,user_ratings_total,name',
                'language' => 'pl',
                'key' => $this->apiKey,
                'reviews_sort' => 'newest',
            ]);

            if (!$response->successful()) {
                Log::warning('Google Places API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->emptyResult();
            }

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                Log::warning('Google Places API returned non-OK status', [
                    'status' => $data['status'] ?? 'unknown',
                    'error_message' => $data['error_message'] ?? null,
                ]);
                return $this->emptyResult();
            }

            $result = $data['result'] ?? [];
            $reviews = collect($result['reviews'] ?? [])
                ->take($limit)
                ->map(fn($review) => [
                    'author_name' => $review['author_name'] ?? 'Anonim',
                    'rating' => $review['rating'] ?? 5,
                    'text' => $review['text'] ?? '',
                    'profile_photo_url' => $review['profile_photo_url'] ?? null,
                    'relative_time_description' => $review['relative_time_description'] ?? '',
                    'time' => $review['time'] ?? 0,
                    'source' => 'google',
                ])
                ->toArray();

            return [
                'reviews' => $reviews,
                'rating' => $result['rating'] ?? null,
                'total_reviews' => $result['user_ratings_total'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Google Places API error', [
                'message' => $e->getMessage(),
            ]);
            return $this->emptyResult();
        }
    }

    /**
     * Clear cached reviews (e.g., after place_id change).
     */
    public function clearCache(): void
    {
        $placeId = Setting::get('google_place_id');
        if ($placeId) {
            try {
                Cache::forget('google_reviews_' . md5($placeId));
            } catch (\BadMethodCallException $e) {
                // Cache driver does not support tagging — skip
            }
        }
    }

    protected function emptyResult(): array
    {
        return [
            'reviews' => [],
            'rating' => null,
            'total_reviews' => 0,
        ];
    }
}
