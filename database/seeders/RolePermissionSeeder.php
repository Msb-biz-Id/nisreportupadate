<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const ROLES = [
        'superadmin',
        'owner',
        'admin_brand',
        'admin_reseller',
        'admin_produksi',
        'admin_keuangan',
        'supervisor',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'brand.view', 'brand.create', 'brand.update', 'brand.delete',
            'user.view', 'user.create', 'user.update', 'user.delete',
            'user.assign-role', 'user.assign-brand',
            'audit.view',
            'dashboard.view-global',
            'dashboard.view-brand',
            'master.manage', 'master.brand', 'master.produk', 'master.production', 'master.view',
            'order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund', 'order.unlock',
            'production.update-progress', 'production.add-reject',
            'finance.view', 'finance.manage-invoice', 'finance.manage-refund',
            'finance.manage-pemasukan', 'finance.manage-pengeluaran',
            'report.view', 'report.export',
            'settings.brand', 'settings.system', 'settings.ai', 'settings.notification',
            'tools.ai',
            'reseller.manage-branches',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roleMap = [
            'superadmin' => $permissions,
            'owner' => [
                'brand.view',
                'user.view',
                'master.view',
                'order.view',
                'finance.view',
                'report.view', 'report.export',
                'dashboard.view-brand',
                'audit.view',
                'tools.ai',
            ],
            'admin_brand' => [
                'master.brand', 'master.produk',
                'order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund',
                'finance.manage-invoice',
                'report.view', 'report.export',
                'settings.notification',
                'dashboard.view-brand',
                'tools.ai',
            ],
            'admin_reseller' => [
                'reseller.manage-branches',
                'brand.view', 'brand.create', 'brand.update', 'brand.delete',
                'user.view', 'user.create', 'user.update', 'user.delete',
                'user.assign-brand',
                'master.brand',
                'order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund',
                'report.view', 'report.export',
                'settings.brand', 'settings.notification',
                'dashboard.view-brand',
                'tools.ai',
            ],
            'admin_produksi' => [
                'order.view',
                'production.update-progress', 'production.add-reject',
                'master.production',   // hanya tahapan progress, bukan katalog produk
                'report.view', 'report.export',
                'dashboard.view-brand',
            ],
            'admin_keuangan' => [
                'order.view',
                'finance.view', 'finance.manage-invoice', 'finance.manage-refund',
                'finance.manage-pemasukan', 'finance.manage-pengeluaran',
                'report.view', 'report.export',
                'dashboard.view-brand',
            ],
            'supervisor' => [
                'order.view',
                'order.unlock',
                'audit.view',
                'dashboard.view-brand',
                'report.view',
            ],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
