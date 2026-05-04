<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\Tenant\ReservationMail;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $tables = Table::orderBy('number')->get();

        $query = Reservation::with('table')->orderBy('reservation_date', 'desc')->orderBy('reservation_time', 'desc');

        if ($request->filled('res_date')) {
            $query->where('reservation_date', $request->res_date);
        }
        if ($request->filled('res_status')) {
            $query->where('status', $request->res_status);
        }

        $reservations = $query->paginate(25)->withQueryString();

        return Inertia::render('Tenant/Manager/Tables/Index', [
            'tables'       => $tables,
            'reservations' => $reservations,
            'resFilters'   => $request->only(['res_date', 'res_status']),
        ]);
    }

    public function updateReservationStatus(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status'   => 'required|in:pending,confirmed,cancelled,completed',
            'table_id' => 'nullable|integer|exists:tables,id',
        ]);

        $oldStatus = $reservation->status;

        // Release previous table if switching away from confirmed/pending with a table
        if ($reservation->table_id && $reservation->table_id !== ($validated['table_id'] ?? null)) {
            Table::where('id', $reservation->table_id)->update(['status' => 'available']);
        }

        $reservation->update([
            'status'   => $validated['status'],
            'table_id' => $validated['table_id'] ?? $reservation->table_id,
        ]);

        // Update table status based on reservation status
        $assignedTableId = $validated['table_id'] ?? $reservation->table_id;
        if ($assignedTableId) {
            $tableStatus = match ($validated['status']) {
                'confirmed' => 'reserved',
                'completed', 'cancelled' => 'available',
                default => null,
            };
            if ($tableStatus) {
                Table::where('id', $assignedTableId)->update(['status' => $tableStatus]);
            }
        }

        if ($reservation->customer_email && $validated['status'] !== $oldStatus
            && in_array($validated['status'], ['confirmed', 'cancelled'])) {
            Mail::to($reservation->customer_email)
                ->queue(new ReservationMail($reservation, $validated['status']));
        }

        return back()->with('success', 'Status rezerwacji zaktualizowany.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number'      => 'required|integer|min:1',
            'name'        => 'nullable|string|max:50',
            'seats'       => 'required|integer|min:1|max:50',
            'min_guests'  => 'nullable|integer|min:1|max:50',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        Table::create($validated);

        return back()->with('success', 'Stolik został dodany.');
    }

    public function update(Request $request, Table $table)
    {
        $validated = $request->validate([
            'number'      => 'required|integer|min:1',
            'name'        => 'nullable|string|max:50',
            'seats'       => 'required|integer|min:1|max:50',
            'min_guests'  => 'nullable|integer|min:1|max:50',
            'location'    => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $table->update($validated);

        return back()->with('success', 'Stolik został zaktualizowany.');
    }

    public function destroy(Table $table)
    {
        $table->delete();

        return back()->with('success', 'Stolik został usunięty.');
    }

    public function toggleActive(Table $table)
    {
        $table->update(['is_active' => !$table->is_active]);

        return back()->with('success', 'Status stolika zmieniony.');
    }

    public function generateQrCode(Table $table)
    {
        $menuUrl    = url('/') . '?table=' . $table->id;
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($menuUrl);

        $table->update([
            'qr_code'      => $menuUrl,
            'qr_image_url' => $qrImageUrl,
        ]);

        return back()->with('success', 'Kod QR został wygenerowany.');
    }
}
