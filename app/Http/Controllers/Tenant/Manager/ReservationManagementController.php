<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\ReservationMail;
use App\Models\Tenant\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ReservationManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::query()->orderBy('reservation_date', 'desc')->orderBy('reservation_time', 'desc');

        if ($request->filled('date')) {
            $query->forDate($request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reservations = $query->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['date', 'status']),
        ]);
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $oldStatus = $reservation->status;
        $reservation->update($validated);

        Log::info('Reservation: zmiana statusu', [
            'reservation_id' => $reservation->id,
            'date'           => $reservation->reservation_date,
            'time'           => $reservation->reservation_time,
            'old_status'     => $oldStatus,
            'new_status'     => $validated['status'],
            'manager_id'     => auth('tenant')->id(),
        ]);

        // Send email to customer when status changes to confirmed or cancelled
        if ($reservation->customer_email && $validated['status'] !== $oldStatus
            && in_array($validated['status'], ['confirmed', 'cancelled'])) {
            Mail::to($reservation->customer_email)
                ->queue(new ReservationMail($reservation, $validated['status']));
        }

        return redirect()->back()->with('success', 'Status rezerwacji zaktualizowany.');
    }
}
