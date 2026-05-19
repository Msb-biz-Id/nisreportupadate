import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge } from '@/Components/ui/badge';
import { roleLabel } from '@/lib/utils';

import AdminBrand from './Dashboards/AdminBrand';
import AdminProduksi from './Dashboards/AdminProduksi';
import Superadmin from './Dashboards/Superadmin';
import Owner from './Dashboards/Owner';
import Finance from './Dashboards/Finance';

const VIEWS = {
    AdminBrand,
    AdminProduksi,
    Superadmin,
    Owner,
    Finance,
};

export default function Dashboard({ role, view, stats }) {
    const ViewComponent = VIEWS[view] ?? AdminBrand;

    return (
        <AppLayout title="Dashboard">
            <Head title="Dashboard" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <Badge variant="secondary" className="mb-2">{roleLabel(role)}</Badge>
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Ringkasan operasional, performance, dan analitik tailored untuk role Anda.
                        </p>
                    </div>
                </div>

                <ViewComponent stats={stats} />
            </div>
        </AppLayout>
    );
}
