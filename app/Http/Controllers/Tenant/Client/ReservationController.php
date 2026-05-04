<?php

namespace App\Http\Controllers\Tenant\Client;

use App\Events\ReservationCreated;
use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Mail\Tenant\ReservationMail;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Table;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index()
    {
        if (!Setting::get('reservations_enabled', false)) {
            abort(404);
        }

        $maxPartySize = Table::where('is_active', true)->max('seats') ?? 20;

        $openingHours = Setting::get('opening_hours');
        if (is_string($openingHours)) {
            $openingHours = json_decode($openingHours, true);
        }

        return Inertia::render('Tenant/Client/Reservation', [
            'maxPartySize' => (int) $maxPartySize,
            'advanceDays'  => (int) Setting::get('reservations_advance_days', 14),
            'slotDuration' => (int) Setting::get('reservations_slot_duration', 120),
            'openingHours' => $openingHours ?: null,
        ]);
    }

    public function store(Request $request)
    {
        if (!Setting::get('reservations_enabled', false)) {
            abort(404);
        }

        $advanceDays = (int) Setting::get('reservations_advance_days', 14);
        $slotMinutes = (int) Setting::get('reservations_slot_duration', 120);
        $capacity    = (int) Setting::get('reservations_capacity_per_slot', 30);
        $maxParty    = Table::where('is_active', true)->max('seats') ?? 50;

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:30',
            'customer_email'   => 'nullable|email|max:255',
            'reservation_date' => 'required|date|after_or_equal:today|before_or_equal:' . now()->addDays($advanceDays)->toDateString(),
            'reservation_time' => 'required|date_format:H:i',
            'party_size'       => "required|integer|min:1|max:{$maxParty}",
            'notes'            => 'nullable|string|max:500',
        ]);

        $validated['customer_phone'] = PhoneHelper::normalize($validated['customer_phone']) ?? $validated['customer_phone'];

        // Validate against opening hours
        $openingHours = Setting::get('opening_hours');
        if (is_string($openingHours)) {
            $openingHours = json_decode($openingHours, true);
        }
        if (!empty($openingHours)) {
            $dayName = strtolower(\Carbon\Carbon::parse($validated['reservation_date'])->format('l'));
            $dayData = $openingHours[$dayName] ?? null;
            $isEnabled = isset($dayData['enabled']) ? (bool) $dayData['enabled'] : !($dayData['closed'] ?? false);

            if (!$dayData || !$isEnabled) {
                return back()->withErrors([
                    'reservation_date' => 'Restauracja jest w tym dniu zamknięta.',
                ])->withInput();
            }

            $timeToMinutes = fn (string $t): int => (fn ($parts) => (int) $parts[0] * 60 + (int) $parts[1])(explode(':', $t . ':00'));
            $openMinutes  = $timeToMinutes($dayData['open'] ?? '00:00');
            $closeMinutes = $timeToMinutes($dayData['close'] ?? '23:59');
            $resMinutes   = $timeToMinutes($validated['reservation_time']);

            if ($resMinutes < $openMinutes || $resMinutes >= $closeMinutes) {
                return back()->withErrors([
                    'reservation_time' => "Restauracja jest otwarta w tym dniu w godzinach {$dayData['open']}–{$dayData['close']}.",
                ])->withInput();
            }
        }

        // Conflict detection: sum of party_size for overlapping slots on the same day
        [$newH, $newM] = explode(':', $validated['reservation_time']);
        $newMinutes = (int) $newH * 60 + (int) $newM;

        $occupied = Reservation::whereDate('reservation_date', $validated['reservation_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->get()
            ->sum(function ($r) use ($newMinutes, $slotMinutes) {
                [$h, $m] = explode(':', substr($r->reservation_time, 0, 5));
                $existing = (int) $h * 60 + (int) $m;
                if (abs($existing - $newMinutes) < $slotMinutes) {
                    return $r->party_size;
                }
                return 0;
            });

        if ($occupied + $validated['party_size'] > $capacity) {
            Log::info('Reservation: brak miejsc', [
                'date'       => $validated['reservation_date'],
                'time'       => $validated['reservation_time'],
                'party_size' => $validated['party_size'],
                'occupied'   => $occupied,
                'capacity'   => $capacity,
            ]);
            return back()->withErrors([
                'reservation_time' => 'Brak wolnych miejsc w wybranym terminie. Wybierz inną godzinę.',
            ])->withInput();
        }

        $customer = auth('customer')->user();

        $reservation = Reservation::create([
            ...$validated,
            'status'      => 'pending',
            'customer_id' => $customer?->id,
        ]);

        Log::info('Reservation: nowa rezerwacja', [
            'reservation_id' => $reservation->id,
            'date'           => $reservation->reservation_date,
            'time'           => $reservation->reservation_time,
            'party_size'     => $reservation->party_size,
            'customer_name'  => $reservation->customer_name,
            'customer_id'    => $reservation->customer_id,
        ]);

        event(new ReservationCreated($reservation));

        // Send confirmation email to customer
        if ($reservation->customer_email) {
            Mail::to($reservation->customer_email)
                ->queue(new ReservationMail($reservation, 'created'));
        }

        return redirect()->back()->with('success', 'Rezerwacja została wysłana! Potwierdzimy ją.');
    }
}
