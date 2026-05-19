<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('user.view');

        $authUser = $request->user();

        $query = User::query()->with(['roles:id,name', 'brands:id,nama_brand,kode']);

        if (! $authUser->isSuperadmin()) {
            $brandIds = $authUser->brands()->pluck('brands.id');
            $query->whereHas('brands', fn ($q) => $q->whereIn('brands.id', $brandIds));
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $role));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('is_active', $status === 'active');
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();

        $availableBrands = $authUser->isSuperadmin()
            ? Brand::active()->orderBy('nama_brand')->get(['id', 'nama_brand', 'kode'])
            : $authUser->brands()->where('is_active', true)->orderBy('nama_brand')->get(['brands.id', 'nama_brand', 'kode']);

        return Inertia::render('User/Index', [
            'users' => $users,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'role' => $request->string('role')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'rolesAvailable' => $this->rolesForUser($authUser),
            'brandsAvailable' => $availableBrands,
            'can' => [
                'create' => $authUser->can('user.create'),
                'update' => $authUser->can('user.update'),
                'delete' => $authUser->can('user.delete'),
                'assignRole' => $authUser->can('user.assign-role'),
                'assignBrand' => $authUser->can('user.assign-brand'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('user.create');
        $authUser = $request->user();
        $allowedRoles = $this->rolesForUser($authUser);
        $allowedBrandIds = $authUser->isSuperadmin()
            ? Brand::pluck('id')->all()
            : $authUser->brands()->pluck('brands.id')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'brand_ids' => ['required', 'array', 'min:1'],
            'brand_ids.*' => ['string', Rule::in($allowedBrandIds)],
            'default_brand_id' => ['required', 'string', Rule::in($allowedBrandIds)],
        ]);

        if (! in_array($data['default_brand_id'], $data['brand_ids'], true)) {
            return back()->withErrors(['default_brand_id' => 'Default brand harus salah satu brand yang dipilih.']);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$data['role']]);

        $sync = [];
        foreach ($data['brand_ids'] as $bid) {
            $sync[$bid] = [
                'is_default' => $bid === $data['default_brand_id'],
                'assigned_by' => $authUser->id,
                'assigned_at' => now(),
            ];
        }
        $user->brands()->sync($sync);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('user.update');

        $authUser = $request->user();
        if (! $authUser->isSuperadmin()) {
            $authBrandIds = $authUser->brands()->pluck('brands.id')->all();
            $shareBrand = $user->brands()->whereIn('brands.id', $authBrandIds)->exists();
            if (! $shareBrand) {
                abort(403);
            }
        }

        $allowedRoles = $this->rolesForUser($authUser);
        $allowedBrandIds = $authUser->isSuperadmin()
            ? Brand::pluck('id')->all()
            : $authUser->brands()->pluck('brands.id')->all();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'brand_ids' => ['required', 'array', 'min:1'],
            'brand_ids.*' => ['string', Rule::in($allowedBrandIds)],
            'default_brand_id' => ['required', 'string', Rule::in($allowedBrandIds)],
        ]);

        if (! in_array($data['default_brand_id'], $data['brand_ids'], true)) {
            return back()->withErrors(['default_brand_id' => 'Default brand harus salah satu brand yang dipilih.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        $sync = [];
        foreach ($data['brand_ids'] as $bid) {
            $sync[$bid] = [
                'is_default' => $bid === $data['default_brand_id'],
                'assigned_by' => $authUser->id,
                'assigned_at' => now(),
            ];
        }
        $user->brands()->sync($sync);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('user.delete');

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->hasRole('superadmin') && ! $request->user()->isSuperadmin()) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    private function rolesForUser(User $user): array
    {
        if ($user->isSuperadmin()) {
            return RolePermissionSeeder::ROLES;
        }
        // Non-superadmin tidak bisa membuat user dengan role superadmin
        return array_values(array_filter(
            RolePermissionSeeder::ROLES,
            fn ($r) => $r !== 'superadmin'
        ));
    }
}
