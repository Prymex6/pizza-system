<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\Tenant;
use App\Models\Landlord\TenantModification;
use App\Services\TenantModificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModificationController extends Controller
{
    public function index()
    {
        $modifications = TenantModification::on('central')
            ->latest()
            ->get()
            ->map(function ($mod) {
                $patchCount = count($mod->getPatches());
                $tenantCount = $mod->tenant_ids === null ? 'wszystkie' : count($mod->tenant_ids ?? []);

                return [
                    'id'           => $mod->id,
                    'name'         => $mod->name,
                    'code'         => $mod->code,
                    'description'  => $mod->description,
                    'version'      => $mod->version,
                    'author'       => $mod->author,
                    'status'       => $mod->status,
                    'tenant_count' => $tenantCount,
                    'patch_count'  => $patchCount,
                    'created_at'   => $mod->created_at,
                ];
            });

        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Index', [
            'modifications' => $modifications,
            'tenants'       => $tenants,
        ]);
    }

    public function create()
    {
        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Form', [
            'modification' => null,
            'tenants'      => $tenants,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:100|unique:central.tenant_modifications,code|alpha_dash',
            'description' => 'nullable|string|max:2000',
            'version'     => 'nullable|string|max:20',
            'author'      => 'nullable|string|max:100',
            'status'      => 'boolean',
            'tenant_ids'  => 'nullable|array',
            'tenant_ids.*'=> 'string',
            'rules_json'  => 'nullable|string',
        ]);

        $rules = null;
        if (!empty($validated['rules_json'])) {
            $rules = json_decode($validated['rules_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['rules_json' => 'Nieprawidłowy JSON: ' . json_last_error_msg()]);
            }
        }

        TenantModification::on('central')->create([
            'name'       => $validated['name'],
            'code'       => $validated['code'],
            'description'=> $validated['description'],
            'version'    => $validated['version'] ?? '1.0.0',
            'author'     => $validated['author'],
            'status'     => $validated['status'] ?? false,
            'tenant_ids' => $validated['tenant_ids'] ?? null,
            'rules'      => $rules,
        ]);

        return redirect()->route('landlord.modifications.index')
            ->with('success', "Modyfikacja \"{$validated['name']}\" została utworzona.");
    }

    public function edit(TenantModification $modification)
    {
        $tenants = Tenant::on('central')
            ->select('id', 'name', 'status')
            ->orderBy('name')
            ->get();

        return Inertia::render('Landlord/Modifications/Form', [
            'modification' => array_merge($modification->toArray(), [
                'rules_json' => $modification->rules
                    ? json_encode($modification->rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    : '',
            ]),
            'tenants' => $tenants,
        ]);
    }

    public function update(Request $request, TenantModification $modification)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => "required|string|max:100|unique:central.tenant_modifications,code,{$modification->id}|alpha_dash",
            'description' => 'nullable|string|max:2000',
            'version'     => 'nullable|string|max:20',
            'author'      => 'nullable|string|max:100',
            'status'      => 'boolean',
            'tenant_ids'  => 'nullable|array',
            'tenant_ids.*'=> 'string',
            'rules_json'  => 'nullable|string',
        ]);

        $rules = null;
        if (!empty($validated['rules_json'])) {
            $rules = json_decode($validated['rules_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['rules_json' => 'Nieprawidłowy JSON: ' . json_last_error_msg()]);
            }
        }

        $modification->update([
            'name'       => $validated['name'],
            'code'       => $validated['code'],
            'description'=> $validated['description'],
            'version'    => $validated['version'] ?? '1.0.0',
            'author'     => $validated['author'],
            'status'     => $validated['status'] ?? false,
            'tenant_ids' => $validated['tenant_ids'] ?? null,
            'rules'      => $rules,
        ]);

        return redirect()->route('landlord.modifications.index')
            ->with('success', "Modyfikacja \"{$modification->name}\" została zaktualizowana.");
    }

    public function destroy(TenantModification $modification)
    {
        $name = $modification->name;
        $modification->delete();

        return back()->with('success', "Modyfikacja \"{$name}\" została usunięta.");
    }

    /**
     * Apply modifications for a specific tenant (via UI button).
     */
    public function apply(Request $request, TenantModificationService $service)
    {
        $request->validate(['tenant_id' => 'required|string|exists:central.tenants,id']);

        $tenantId = $request->tenant_id;
        $results  = $service->applyForTenant($tenantId);

        $ok    = collect($results)->where('status', 'ok')->count();
        $error = collect($results)->where('status', 'error')->count();

        $msg = "Zastosowano {$ok} modyfikacji";
        if ($error > 0) {
            $msg .= ", {$error} błędów";
        }

        return back()->with($error > 0 ? 'error' : 'success', $msg . '.');
    }

    /**
     * Clear modifications cache for a specific tenant.
     */
    public function clear(Request $request, TenantModificationService $service)
    {
        $request->validate(['tenant_id' => 'required|string|exists:central.tenants,id']);

        $service->clearForTenant($request->tenant_id);

        return back()->with('success', 'Cache modyfikacji wyczyszczony.');
    }

    /**
     * Toggle a modification's status.
     */
    public function toggle(TenantModification $modification)
    {
        $modification->update(['status' => !$modification->status]);

        $state = $modification->status ? 'włączona' : 'wyłączona';

        return back()->with('success', "Modyfikacja \"{$modification->name}\" została {$state}.");
    }
}
