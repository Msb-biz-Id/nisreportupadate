import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import * as Icons from 'lucide-react';
import { ArrowLeft, Sparkles, Loader2, Copy, Check, AlertCircle, RotateCw } from 'lucide-react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { toast } from 'sonner';

function FieldRenderer({ field, value, onChange }) {
    if (field.type === 'select') {
        return (
            <Select value={value || '__none__'} onValueChange={(v) => onChange(v === '__none__' ? '' : v)}>
                <SelectTrigger className="mt-1.5"><SelectValue placeholder="— Pilih —" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="__none__">— Tidak diset —</SelectItem>
                    {field.options.map((o) => (<SelectItem key={o} value={o}>{o}</SelectItem>))}
                </SelectContent>
            </Select>
        );
    }
    if (field.type === 'textarea') {
        return (
            <Textarea value={value ?? ''} onChange={(e) => onChange(e.target.value)} rows={field.rows || 3} className="mt-1.5 font-mono text-sm" />
        );
    }
    if (field.type === 'checkbox_group') {
        const list = Array.isArray(value) ? value : [];
        return (
            <div className="mt-1.5 flex flex-wrap gap-2">
                {field.options.map((o) => {
                    const active = list.includes(o);
                    return (
                        <button
                            key={o}
                            type="button"
                            onClick={() => onChange(active ? list.filter((x) => x !== o) : [...list, o])}
                            className={`rounded-full border px-3 py-1 text-xs font-medium transition ${active ? 'border-primary bg-primary text-primary-foreground' : 'bg-background text-muted-foreground hover:bg-accent'}`}
                        >
                            {o}
                        </button>
                    );
                })}
            </div>
        );
    }
    return <Input value={value ?? ''} onChange={(e) => onChange(e.target.value)} className="mt-1.5" />;
}

export default function AiToolPage({ tool, isConfigured, fields }) {
    const Icon = Icons[tool.icon] ?? Icons.Sparkles;
    const [data, setData] = useState({});
    const [loading, setLoading] = useState(false);
    const [output, setOutput] = useState(null);
    const [copied, setCopied] = useState(false);

    function patch(key, value) {
        setData((d) => ({ ...d, [key]: value }));
    }

    async function submit(e) {
        e?.preventDefault?.();
        setLoading(true);
        setOutput(null);
        try {
            const { data: res } = await axios.post(route('tools.ai.run', tool.slug), data);
            setOutput(res);
            if (!res.success) toast.error('AI gagal merespons. Cek detail di output.');
        } catch (err) {
            const msg = err?.response?.data?.message || err.message;
            toast.error(msg);
            setOutput({ success: false, text: msg });
        } finally {
            setLoading(false);
        }
    }

    function copyOutput() {
        if (!output?.text) return;
        navigator.clipboard.writeText(output.text);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
        toast.success('Output disalin ke clipboard');
    }

    return (
        <AppLayout title={tool.label}>
            <Head title={tool.label} />

            <div className="space-y-5">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="sm">
                        <Link href={route('tools.ai.index')}><ArrowLeft className="h-4 w-4" /> Kembali</Link>
                    </Button>
                </div>

                {!isConfigured && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                        <AlertCircle className="mb-1 inline h-4 w-4" /> Mock mode aktif — output simulasi. Konfigurasi Gemini API di <Link href={route('settings.integrasi')} className="font-semibold underline">Pengaturan → Integrasi</Link>.
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-start gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Icon className="h-5 w-5" />
                            </div>
                            <div>
                                <CardTitle>{tool.label}</CardTitle>
                                <CardDescription>{tool.description}</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <div className="grid grid-cols-1 gap-4 lg:grid-cols-5">
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">Input</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-3">
                                {fields.map((f) => (
                                    <div key={f.name}>
                                        <Label htmlFor={f.name}>
                                            {f.label} {f.required && <span className="text-destructive">*</span>}
                                        </Label>
                                        <FieldRenderer field={f} value={data[f.name]} onChange={(v) => patch(f.name, v)} />
                                    </div>
                                ))}
                                <Button type="submit" disabled={loading} className="w-full">
                                    {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
                                    {loading ? 'AI sedang berpikir…' : 'Generate dengan AI'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-3">
                        <CardHeader className="flex flex-row items-center justify-between gap-2">
                            <div>
                                <CardTitle className="text-base">Hasil AI</CardTitle>
                                {output?.model && (
                                    <Badge variant="outline" className="mt-1 text-[10px]">
                                        Model: {output.model} · {output.tokens || 0} tokens
                                    </Badge>
                                )}
                            </div>
                            {output?.text && (
                                <div className="flex gap-1">
                                    <Button variant="outline" size="sm" onClick={copyOutput}>
                                        {copied ? <Check className="h-3.5 w-3.5" /> : <Copy className="h-3.5 w-3.5" />}
                                        {copied ? 'Disalin' : 'Salin'}
                                    </Button>
                                    <Button variant="outline" size="sm" onClick={submit} disabled={loading}>
                                        <RotateCw className="h-3.5 w-3.5" /> Regen
                                    </Button>
                                </div>
                            )}
                        </CardHeader>
                        <CardContent>
                            {loading && (
                                <div className="flex h-64 items-center justify-center text-sm text-muted-foreground">
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" /> AI sedang menulis…
                                </div>
                            )}
                            {!loading && !output && (
                                <div className="flex h-64 items-center justify-center rounded-lg border-2 border-dashed text-sm text-muted-foreground">
                                    Isi form di kiri lalu klik "Generate".
                                </div>
                            )}
                            {output && (
                                <div className={`rounded-lg border p-4 ${output.success ? 'bg-card' : 'border-destructive bg-destructive/5'}`}>
                                    {output.mock && (
                                        <Badge variant="warning" className="mb-2">Mock Output (API key belum diset)</Badge>
                                    )}
                                    <pre className="whitespace-pre-wrap break-words font-sans text-sm leading-relaxed">{output.text || output.error || '(kosong)'}</pre>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
