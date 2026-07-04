import { lazy, Suspense } from 'react';

const ApexChart = lazy(() => import('react-apexcharts'));

export default function Chart({ type = 'bar', series, options = {}, height = 280 }) {
    const baseOptions = {
        chart: {
            toolbar: { show: false },
            fontFamily: 'Inter, system-ui, sans-serif',
            animations: { speed: 350 },
            zoom: { enabled: false },
            panning: { enabled: false },
            selection: { enabled: false },
        },
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(148, 163, 184, 0.15)', strokeDashArray: 4 },
        legend: { fontSize: '12px', fontWeight: 500 },
        tooltip: { theme: 'light' },
        stroke: { width: type === 'line' || type === 'area' ? 2.5 : 0, curve: 'smooth' },
        ...options,
    };

    return (
        <Suspense fallback={<div className="flex h-[280px] items-center justify-center text-xs text-muted-foreground">Memuat chart…</div>}>
            <ApexChart type={type} series={series} options={baseOptions} height={height} width="100%" />
        </Suspense>
    );
}
