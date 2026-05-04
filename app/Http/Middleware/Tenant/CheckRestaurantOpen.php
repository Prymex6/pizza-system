<?php

namespace App\Http\Middleware\Tenant;

use App\Models\Tenant\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckRestaurantOpen
{
    public function handle(Request $request, Closure $next)
    {
        // Manual orders pause (set by staff via quick controls)
        if (Setting::get('orders_paused', false)) {
            return response()->json(['message' => 'Zamówienia są chwilowo wstrzymane. Zapraszamy za chwilę.'], 422);
        }

        // Vacation / maintenance mode
        if (Setting::get('vacation_mode', false)) {
            $message = Setting::get('vacation_message', 'Restauracja jest chwilowo niedostępna. Zapraszamy później.');
            return response()->json(['message' => $message], 422);
        }

        // Specific closure days (#31)
        $closedDays = Setting::get('closed_days');
        if ($closedDays) {
            $timezone = Setting::get('timezone', 'Europe/Warsaw');
            $today = now()->setTimezone($timezone)->format('Y-m-d');
            $days = is_string($closedDays) ? json_decode($closedDays, true) : $closedDays;
            if (!empty($days) && in_array($today, $days)) {
                return response()->json(['message' => 'Restauracja jest dzisiaj zamknięta.'], 422);
            }
        }

        // Opening hours check (using restaurant timezone, not server timezone)
        $openingHours = Setting::get('opening_hours');
        if ($openingHours) {
            $timezone = Setting::get('timezone', 'Europe/Warsaw');
            $now = now()->setTimezone($timezone);

            $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $dayKey = $dayNames[$now->dayOfWeek];
            $day = $openingHours[$dayKey] ?? null;

            if (!$day || empty($day['enabled'])) {
                return response()->json(['message' => 'Restauracja jest dzisiaj zamknięta.'], 422);
            }

            $currentMinutes = $now->hour * 60 + $now->minute;

            [$openH, $openM] = explode(':', $day['open'] ?? '00:00');
            [$closeH, $closeM] = explode(':', $day['close'] ?? '23:59');

            $openMinutes  = (int) $openH * 60 + (int) $openM;
            $closeMinutes = (int) $closeH * 60 + (int) $closeM;

            // 00:00 as closing time means end of day (midnight = 1440 min)
            if ($closeMinutes === 0) {
                $closeMinutes = 1440;
            }

            if ($currentMinutes < $openMinutes || $currentMinutes >= $closeMinutes) {
                $openTime = $day['open'] ?? '?';
                return response()->json([
                    'message' => "Restauracja jest zamknięta. Zamawianie online możliwe od godz. {$openTime}.",
                ], 422);
            }
        }

        return $next($request);
    }
}
