<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Helpers\PhoneHelper;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StaffController extends Controller
{
    public function index()
    {
        $staffList = User::latest()->paginate(15);

        return Inertia::render('Tenant/Manager/Staff/Index', [
            'staffList' => $staffList,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Staff/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:chef,waiter,driver,cashier,manager',
            'phone' => 'nullable|string|max:20',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['phone'] = PhoneHelper::normalize($validated['phone'] ?? null);

        $user = User::create($validated);
        Log::info('Staff: pracownik dodany', ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role, 'manager_id' => Auth::guard('tenant')->id()]);

        return redirect()->route('tenant.manager.staff.index')
            ->with('success', 'Pracownik został dodany');
    }

    public function edit(User $staff)
    {
        return Inertia::render('Tenant/Manager/Staff/Form', [
            'user' => $staff,
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:chef,waiter,driver,cashier,manager',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $validated['phone'] = PhoneHelper::normalize($validated['phone'] ?? null);

        $staff->update($validated);
        Log::info('Staff: pracownik zaktualizowany', ['user_id' => $staff->id, 'email' => $staff->email, 'role' => $staff->role, 'manager_id' => Auth::guard('tenant')->id()]);

        return redirect()->route('tenant.manager.staff.index')
            ->with('success', 'Pracownik został zaktualizowany');
    }

    public function destroy(User $staff)
    {
        if ($staff->id === Auth::guard('tenant')->id()) {
            return back()->withErrors(['error' => 'Nie możesz usunąć własnego konta.']);
        }

        Log::info('Staff: pracownik usunięty', ['user_id' => $staff->id, 'email' => $staff->email, 'role' => $staff->role, 'manager_id' => Auth::guard('tenant')->id()]);
        $staff->delete();

        return back()->with('success', 'Pracownik został usunięty');
    }
}
