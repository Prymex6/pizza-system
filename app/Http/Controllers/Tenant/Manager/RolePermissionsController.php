<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RolePermissionsController extends Controller
{
    // Predefined permissions with labels
    public const PERMISSIONS = [
        'update_order_status' => 'Zmiana statusu zamówień',
        'accept_phone_orders' => 'Przyjmowanie zamówień telefonicznych (POS)',
        'view_cash'           => 'Podgląd przychodów i kasy',
        'manage_reservations' => 'Zarządzanie rezerwacjami',
        'view_customers'      => 'Podgląd danych klientów',
        'send_reports'        => 'Wysyłanie raportów do managera',
    ];

    // Configurable roles
    public const ROLES = ['chef', 'waiter', 'driver', 'cashier'];

    // Default permissions per role (used during install/setup)
    public const DEFAULTS = [
        'chef'    => ['update_order_status', 'send_reports'],
        'waiter'  => ['update_order_status', 'accept_phone_orders', 'manage_reservations', 'view_customers', 'send_reports'],
        'driver'  => ['update_order_status', 'view_customers', 'send_reports'],
        'cashier' => ['update_order_status', 'accept_phone_orders', 'view_cash', 'send_reports'],
    ];

    public function index()
    {
        return Inertia::render('Tenant/Manager/RolePermissions', [
            'permissions' => self::PERMISSIONS,
            'roles' => self::ROLES,
            'currentPermissions' => RolePermission::allGrouped(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => ['boolean'],
        ]);

        // Clear existing and rebuild inside a transaction to prevent data loss on failure
        DB::transaction(function () use ($validated) {
            RolePermission::whereIn('role', self::ROLES)->delete();

            foreach (self::ROLES as $role) {
                foreach (array_keys(self::PERMISSIONS) as $perm) {
                    if (!empty($validated['permissions'][$role][$perm])) {
                        RolePermission::create([
                            'role' => $role,
                            'permission' => $perm,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Uprawnienia zostały zaktualizowane.');
    }
}
