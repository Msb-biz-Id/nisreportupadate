<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('user.assign-role');

        $roles = Role::withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });

        $permissions = Permission::orderBy('name')->get(['id', 'name']);

        // Group permissions logically for the frontend UI
        $groupedPermissions = [
            'Brand & User Management' => ['brand.view', 'brand.create', 'brand.update', 'brand.delete', 'user.view', 'user.create', 'user.update', 'user.delete', 'user.assign-role', 'user.assign-brand'],
            'Dashboard & Audit Logs' => ['dashboard.view-global', 'dashboard.view-brand', 'audit.view'],
            'Master Data' => ['master.manage', 'master.brand', 'master.produk', 'master.production'],
            'Order Operations' => ['order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund', 'order.unlock'],
            'Production' => ['production.update-progress', 'production.add-reject'],
            'Finance' => ['finance.view', 'finance.manage-invoice', 'finance.manage-refund', 'finance.manage-pemasukan', 'finance.manage-pengeluaran'],
            'Tools & System Settings' => ['report.view', 'report.export', 'settings.brand', 'settings.system', 'settings.ai', 'settings.notification', 'tools.ai', 'reseller.manage-branches'],
        ];

        return Inertia::render('Settings/Roles', [
            'roles' => $roles,
            'all_permissions' => $permissions,
            'grouped_permissions' => $groupedPermissions,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('user.assign-role');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => strtolower(str_replace(' ', '_', $data['name'])),
            'guard_name' => 'web',
        ]);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(Request $request, Role $role)
    {
        Gate::authorize('user.assign-role');

        // Prevent modifying the superadmin role names to avoid system lockout
        if ($role->name === 'superadmin') {
            $data = $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);

            $role->update([
                'name' => strtolower(str_replace(' ', '_', $data['name'])),
            ]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        Gate::authorize('user.assign-role');

        if (in_array($role->name, ['superadmin', 'owner'], true)) {
            return back()->with('error', 'Role bawaan sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Role tidak dapat dihapus karena masih digunakan oleh user.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
