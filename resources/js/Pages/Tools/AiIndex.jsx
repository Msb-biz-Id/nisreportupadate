import { Head, Link } from '@inertiajs/react';
import * as Icons from 'lucide-react';
import { Sparkles, AlertCircle, ArrowRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { formatDateTime } from '@/lib/utils';

export default function AiIndex({ tools, isConfigured, recentLogs }) {
    return (
        <AppLayout title="AI Tools">
            <Head title="AI Tools" />

            <div className="space-y-5">
                {!isConfigured && (
                    <Card className="border-amber-200 bg-amber-50">
                        <CardContent className="flex items-start gap-3 p-4">
                            <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                            <div className="flex-1">
                                <div className="font-semibold text-amber-900">Mode Mock Aktif</div>
                                <p className="text-sm text-amber-800">
                                    Gemini API key belum dikonfigurasi. Semua AI tool akan menghasilkan output simulasi. Superadmin dapat mengatur API key di <Link href={route('settings.integrasi')} className="font-semibold underline">Pengaturan → Integrasi</Link>.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="flex flex-col gap-1">
                    <div className="flex items-center gap-2">
                        <Sparkles className="h-6 w-6 text-primary" />
                        <h1 className="text-2xl font-bold">AI Tools</h1>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Asisten AI untuk mempercepat tugas-tugas admin: balas chat, copywriting, ringkas pesanan, format FO, dan handle komplain.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {tools.map((t) => {
                        const Icon = Icons[t.icon] ?? Icons.Sparkles;
                        return (
                            <Link key={t.slug} href={route('tools.ai.show', t.slug)} className="group">
                                <Card className="h-full transition hover:border-primary hover:shadow-md">
                                    <CardContent className="p-5">
                                        <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <h3 className="text-base font-semibold group-hover:text-primary">{t.label}</h3>
                                        <p className="mt-1 text-xs text-muted-foreground">{t.description}</p>
                                        <div className="mt-3 flex items-center text-xs font-medium text-primary">
                                            Buka tool <ArrowRight className="h-3 w-3 transition group-hover:translate-x-1" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>

                {recentLogs?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Riwayat Terakhir</CardTitle>
                            <CardDescription>10 penggunaan AI terbaru Anda.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2">
                                {recentLogs.map((l) => (
                                    <li key={l.id} className="flex items-center justify-between rounded-lg border p-2 text-sm">
                                        <div>
                                            <Badge variant="outline" className="text-[10px]">{l.tool_slug}</Badge>
                                            <span className="ml-2 text-xs text-muted-foreground">{formatDateTime(l.created_at)}</span>
                                        </div>
                                        <div className="flex items-center gap-2 text-xs">
                                            {l.tokens_used && <span className="font-mono text-muted-foreground">{l.tokens_used} tokens</span>}
                                            <Badge variant={l.status === 'success' ? 'success' : 'destructive'}>{l.status}</Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
