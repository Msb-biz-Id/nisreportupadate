import { Head, Link } from '@inertiajs/react';
import { AlertCircle, ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';

export default function NotEligible({ reason, availableBrands }) {
    return (
        <AppLayout title="Comparison Report">
            <Head title="Comparison Report" />

            <Card className="border-amber-200 bg-amber-50">
                <CardContent className="flex flex-col items-start gap-3 p-8">
                    <AlertCircle className="h-8 w-8 text-amber-600" />
                    <h1 className="text-xl font-bold text-amber-900">Tidak Bisa Comparison</h1>
                    <p className="text-sm text-amber-900">{reason}</p>
                    {availableBrands.length > 0 && (
                        <p className="text-sm text-amber-800">
                            Brand yang tersedia: {availableBrands.map((b) => b.nama_brand).join(', ')}
                        </p>
                    )}
                    <Button asChild variant="outline">
                        <Link href={route('dashboard')}><ArrowLeft className="h-4 w-4" /> Kembali ke Dashboard</Link>
                    </Button>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
