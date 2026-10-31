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
        'admin_hcm',
        'staff_hcm',
        'admin_purchasing',
        'staff_purchasing',
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
            'order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund', 'order.lock-unlock',
            'production.update-progress', 'production.add-reject',
            'finance.view', 'finance.manage-invoice', 'finance.manage-refund',
            'finance.manage-pemasukan', 'finance.manage-pengeluaran', 'finance.sign-paid',
            'report.view', 'report.export',
            'settings.brand', 'settings.system', 'settings.ai', 'settings.notification',
            'tools.ai',
            'reseller.manage-branches',

            // HCM / Kepegawaian Permissions
            'hcm.view',
            'hcm.manage-master',
            'hcm.manage-employees',
            'hcm.manage-contracts',
            'hcm.manage-compensation',
            'hcm.manage-attendance',
            'hcm.manage-overtime',
            'hcm.sign-overtime',
            'hcm.manage-meal-allowance',
            'hcm.sign-meal-allowance',
            'hcm.manage-rewards',
            'hcm.manage-recruitment',
            'hcm.manage-documents',
            'hcm.manage-agenda',
            'hcm.manage-events',
            'hcm.export-reports',
            'hcm.manage-settings',

            // Purchasing & Asset Management Permissions
            'purchasing.view',
            'purchasing.manage-orders',
            'purchasing.approve-pic',
            'purchasing.manage-payments',
            'purchasing.manage-assets',
            'purchasing.mutate-assets',
            'purchasing.retire-assets',
            'purchasing.manage-materials',
            'purchasing.manage-vendors',
            'purchasing.manage-master',
            'purchasing.search-history',
            'purchasing.export-reports',
            'purchasing.approve-finance',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Clean up old order.unlock permission if exists
        Permission::where('name', 'order.unlock')->delete();

        $roleMap = [
            'superadmin' => $permissions,
            'owner' => [
                'brand.view',
                'user.view',
                'master.view',
                'order.view',
                'order.lock-unlock',
                'finance.view',
                'report.view', 'report.export',
                'dashboard.view-brand',
                'audit.view',
                'tools.ai',
                'hcm.view',
                'hcm.export-reports',
                'purchasing.view',
                'purchasing.export-reports',
                'purchasing.search-history',
            ],
            'admin_brand' => [
                'master.brand', 'master.produk',
                'order.view', 'order.create', 'order.update', 'order.delete', 'order.publish', 'order.refund',
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
                'hcm.manage-attendance',
                'hcm.manage-overtime',
                'purchasing.approve-pic',
            ],
            'admin_keuangan' => [
                'order.view',
                'finance.view', 'finance.manage-invoice', 'finance.manage-refund',
                'finance.manage-pemasukan', 'finance.manage-pengeluaran', 'finance.sign-paid',
                'report.view', 'report.export',
                'dashboard.view-brand',
                'hcm.manage-meal-allowance',
                'hcm.manage-overtime',
                'purchasing.view',
                'purchasing.manage-payments',
                'purchasing.approve-finance',
            ],
            'supervisor' => [
                'order.view',
                'order.lock-unlock',
                'audit.view',
                'dashboard.view-brand',
                'report.view',
                'hcm.view',
                'purchasing.view',
                'purchasing.approve-pic',
            ],
            'admin_hcm' => [
                'brand.view',
                'user.view',
                'dashboard.view-global',
                'dashboard.view-brand',
                'hcm.view',
                'hcm.manage-master',
                'hcm.manage-employees',
                'hcm.manage-contracts',
                'hcm.manage-compensation',
                'hcm.manage-attendance',
                'hcm.manage-overtime',
                'hcm.sign-overtime',
                'hcm.manage-meal-allowance',
                'hcm.sign-meal-allowance',
                'hcm.manage-rewards',
                'hcm.manage-recruitment',
                'hcm.manage-documents',
                'hcm.manage-agenda',
                'hcm.manage-events',
                'hcm.export-reports',
                'hcm.manage-settings',
                'purchasing.approve-pic',
            ],
            'staff_hcm' => [
                'brand.view',
                'dashboard.view-brand',
                'hcm.view',
                'hcm.manage-employees',
                'hcm.manage-contracts',
                'hcm.manage-attendance',
                'hcm.manage-overtime',
                'hcm.manage-meal-allowance',
                'hcm.manage-rewards',
                'hcm.manage-recruitment',
                'hcm.manage-documents',
                'hcm.manage-agenda',
                'hcm.manage-events',
                'hcm.export-reports',
            ],
            'admin_purchasing' => [
                'brand.view',
                'dashboard.view-global',
                'dashboard.view-brand',
                'purchasing.view',
                'purchasing.manage-orders',
                'purchasing.approve-pic',
                'purchasing.manage-payments',
                'purchasing.manage-assets',
                'purchasing.mutate-assets',
                'purchasing.retire-assets',
                'purchasing.manage-materials',
                'purchasing.manage-vendors',
                'purchasing.manage-master',
                'purchasing.search-history',
                'purchasing.export-reports',
            ],
            'staff_purchasing' => [
                'brand.view',
                'dashboard.view-brand',
                'purchasing.view',
                'purchasing.manage-orders',
                'purchasing.manage-assets',
                'purchasing.manage-materials',
                'purchasing.manage-vendors',
                'purchasing.search-history',
                'purchasing.export-reports',
            ],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
