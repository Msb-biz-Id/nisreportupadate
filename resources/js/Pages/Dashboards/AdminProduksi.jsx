import { Link } from '@inertiajs/react';
import Chart from '@/Components/Chart';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Factory, ArrowUpRight } from 'lucide-react';
import { StatGrid, StatusBreakdown, POListWidget } from '@/Components/Widgets';

export default function AdminProduksi({ stats }) {
    const rijekJenis = stats.rijek_by_jenis ?? [];
    const rijekTingkat = stats.rijek_by_tingkat ?? [];
    const progressDist = stats.progress_distribution ?? [];

    return (
        <div className="space-y-6">
            <StatGrid cards={stats.cards ?? []} />

            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-base"><Factory className="h-4 w-4 text-primary" /> Quick Access Produksi</CardTitle>
                        <CardDescription>Akses cepat ke Kanban board untuk update progress.</CardDescription>
                    </div>
                    <Button asChild>
                        <Link href={route('produksi.kanban')}>Buka Kanban <ArrowUpRight className="h-4 w-4" /></Link>
                    </Button>
                </CardHeader>
            </Card>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <StatusBreakdown items={stats.status_breakdown ?? []} />

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Rijek per Jenis</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rijekJenis.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada rijek.</p>
                        ) : (
                            <Chart
                                type="donut"
                                height={260}
                                series={rijekJenis.map((r) => r.count)}
                                options={{
                                    labels: rijekJenis.map((r) => r.label),
                                    colors: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6'],
                                    legend: { position: 'bottom' },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Rijek per Tingkat</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {rijekTingkat.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">Belum ada rijek.</p>
                        ) : (
                            <Chart
                                type="bar"
                                height={260}
                                series={[{ name: 'Jumlah', data: rijekTingkat.map((r) => r.count) }]}
                                options={{
                                    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
                                    xaxis: { categories: rijekTingkat.map((r) => r.label) },
                                    colors: ['#EF4444'],
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Distribusi On-Progress per Tahapan</CardTitle>
                    <CardDescription>Jumlah PO yang sedang dikerjakan di setiap tahapan progress.</CardDescription>
                </CardHeader>
                <CardContent>
                    {progressDist.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">Tidak ada tahapan aktif saat ini.</p>
                    ) : (
                        <Chart
                            type="bar"
                            height={300}
                            series={[{ name: 'PO on-progress', data: progressDist.map((r) => r.count) }]}
                            options={{
                                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                                xaxis: { categories: progressDist.map((r) => r.label), labels: { rotate: -30, style: { fontSize: '10px' } } },
                                colors: ['#F59E0B'],
                            }}
                        />
                    )}
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <POListWidget
                    title="Deadline Mendekat (≤7 hari)"
                    description="Prioritaskan PO ini segera."
                    items={stats.deadline_mendekat ?? []}
                    link={{ href: route('produksi.kanban'), label: 'Buka Kanban' }}
                />
                <POListWidget
                    title="PO Terlambat"
                    description="Sudah melewati deadline customer."
                    items={stats.po_terlambat ?? []}
                    link={{ href: route('orders.index') + '?status=delay', label: 'Lihat semua' }}
                />
            </div>
        </div>
    );
}
