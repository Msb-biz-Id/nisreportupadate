import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import {
    Bot,
    Key,
    Activity,
    Copy,
    Check,
    RefreshCw,
    Trash2,
    Shield,
    ExternalLink,
    AlertCircle,
    CheckCircle2,
    Terminal,
    Layers,
    Clock,
    Zap,
    Send,
    Eye,
    EyeOff,
    PlusCircle,
    Pencil
} from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { cn } from '@/lib/utils';

export default function McpIndex({ keys = [], recentLogs = [], tools = [], stats = {}, flash = {} }) {
    const [activeTab, setActiveTab] = useState('keys');
    const [copiedMap, setCopiedMap] = useState({});
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [selectedLog, setSelectedLog] = useState(null);
    const [editingKey, setEditingKey] = useState(null);
    const [editName, setEditName] = useState('');
    const [isUpdating, setIsUpdating] = useState(false);

    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        client_type: 'chatgpt',
        rate_limit_per_minute: 60,
        expires_days: ''
    });

    const handleCopy = (text, keyId) => {
        if (!text) return;
        navigator.clipboard.writeText(text);
        setCopiedMap(prev => ({ ...prev, [keyId]: true }));
        setTimeout(() => {
            setCopiedMap(prev => ({ ...prev, [keyId]: false }));
        }, 2000);
    };

    const handleCreateKey = (e) => {
        e.preventDefault();
        post(route('settings.mcp.generate_key'), {
            onSuccess: () => {
                setIsCreateModalOpen(false);
                reset();
            }
        });
    };

    const handleToggleKey = (id) => {
        router.post(route('settings.mcp.toggle_key', id), {}, { preserveScroll: true });
    };

    const handleRevokeKey = (id, name) => {
        if (confirm(`Apakah Anda yakin ingin menghapus API Key "${name}" secara permanen? Client AI yang menggunakan token ini tidak dapat mengakses lagi.`)) {
            router.delete(route('settings.mcp.revoke_key', id), { preserveScroll: true });
        }
    };

    const handleOpenEdit = (keyItem) => {
        setEditingKey(keyItem);
        setEditName(keyItem.name || '');
    };

    const handleSaveEdit = (e) => {
        e.preventDefault();
        if (!editingKey || !editName.trim()) return;

        setIsUpdating(true);
        router.put(route('settings.mcp.update_key', editingKey.id), {
            name: editName.trim()
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setEditingKey(null);
                setIsUpdating(false);
            },
            onError: () => {
                setIsUpdating(false);
            }
        });
    };

    const handleClearCache = () => {
        router.post(route('settings.mcp.clear_cache'), {}, { preserveScroll: true });
    };

    const serverUrl = stats.server_url || window.location.origin;

    const endpoints = {
        universal: `${serverUrl}/api/ai/mcp`,
        openapi: `${serverUrl}/api/ai/mcp`,
        rpc: `${serverUrl}/api/ai/mcp`,
        sse: `${serverUrl}/api/ai/mcp/sse`,
        openai_tools: `${serverUrl}/api/ai/mcp/openai-tools.json`,
        gemini_tools: `${serverUrl}/api/ai/mcp/gemini-tools.json`,
    };

    const claudeDesktopConfig = JSON.stringify({
        "mcpServers": {
            "garment-os": {
                "command": "npx",
                "args": [
                    "-y",
                    "mcp-remote",
                    endpoints.sse,
                    "--header",
                    "Authorization: Bearer YOUR_MCP_API_KEY"
                ]
            }
        }
    }, null, 2);

    return (
        <AppLayout>
            <Head title="MCP & Multi-AI Copilot Management" />

            <div className="space-y-6 pb-12">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2">
                                <Bot className="h-7 w-7 text-indigo-600 dark:text-indigo-400" />
                                Model Context Protocol (MCP) & AI Integration
                            </h1>
                            <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span className="h-2 w-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse" />
                                Live & Optimized
                            </Badge>
                        </div>
                        <p className="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            Integrasi protokol standar untuk ChatGPT Custom Actions, Claude Desktop, Google Gemini, OpenCode, Cursor, dan Cline dengan proteksi performa Shared Hosting.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleClearCache}
                            className="text-xs"
                            title="Segarkan cache token-dense untuk memuat update order seketika"
                        >
                            <RefreshCw className="h-3.5 w-3.5 mr-1.5 text-slate-600" />
                            Refresh AI Cache
                        </Button>
                        <Button
                            onClick={() => setIsCreateModalOpen(true)}
                            size="sm"
                            className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs"
                        >
                            <PlusCircle className="h-4 w-4 mr-1.5" />
                            Generate API Key
                        </Button>
                    </div>
                </div>

                {/* UNIVERSAL GENERAL MCP URL CARD */}
                <div className="p-4 rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/80 via-white to-indigo-50/50 dark:from-indigo-950/40 dark:via-slate-900 dark:to-indigo-950/20 dark:border-indigo-800 shadow-xs">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div className="space-y-1">
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-sm text-indigo-950 dark:text-indigo-200">
                                    🌐 Universal MCP URL (1 URL General untuk Semua AI):
                                </span>
                                <Badge className="bg-indigo-600 text-white text-[10px]">General Endpoint</Badge>
                            </div>
                            <p className="text-xs text-slate-600 dark:text-slate-400">
                                Gunakan 1 URL tunggal ini untuk semua platform: <strong>ChatGPT Actions</strong> (otomatis mengenali skema OpenAPI), <strong>Claude Desktop</strong>, <strong>Gemini</strong>, <strong>Cursor</strong>, dan <strong>OpenCode</strong>.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <code className="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-950 border border-indigo-200 dark:border-indigo-800 font-mono text-xs text-indigo-700 dark:text-indigo-300 font-bold select-all">
                                {endpoints.universal}
                            </code>
                            <Button
                                size="sm"
                                onClick={() => handleCopy(endpoints.universal, 'universal_url')}
                                className="bg-indigo-600 hover:bg-indigo-700 text-white h-8 px-3 shrink-0"
                            >
                                {copiedMap['universal_url'] ? <Check className="h-4 w-4 mr-1 text-emerald-300" /> : <Copy className="h-4 w-4 mr-1" />}
                                <span className="text-xs">{copiedMap['universal_url'] ? 'Tersalin' : 'Salin URL'}</span>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Newly Created Token Alert (One-Time View) */}
                {flash.created_token && (
                    <div className="p-4 rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-800 text-amber-900 dark:text-amber-200 shadow-xs animate-in fade-in duration-300">
                        <div className="flex items-start gap-3">
                            <Key className="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                            <div className="flex-1 space-y-1">
                                <p className="font-semibold text-sm">
                                    Simpan API Key Baru ({flash.created_key_name}):
                                </p>
                                <p className="text-xs text-amber-700 dark:text-amber-300">
                                    Token ini hanya ditampilkan satu kali saja demi keamanan enkripsi SHA-256. Harap segera salin ke konfigurasi AI Anda.
                                </p>
                                <div className="flex items-center gap-2 mt-2">
                                    <code className="px-3 py-1.5 rounded-lg bg-white dark:bg-slate-900 border font-mono text-xs text-indigo-700 dark:text-indigo-300 font-semibold select-all break-all">
                                        {flash.created_token}
                                    </code>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => handleCopy(flash.created_token, 'new_token')}
                                        className="h-8 px-2.5"
                                    >
                                        {copiedMap['new_token'] ? <Check className="h-4 w-4 text-emerald-600" /> : <Copy className="h-4 w-4" />}
                                        <span className="ml-1 text-xs">{copiedMap['new_token'] ? 'Tersalin' : 'Salin'}</span>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Statistics Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Card className="border-slate-200 dark:border-slate-800 shadow-xs">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs flex items-center justify-between">
                                <span>Active Keys</span>
                                <Key className="h-3.5 w-3.5 text-indigo-500" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {stats.active_keys || 0} <span className="text-xs font-normal text-slate-500">/ {stats.total_keys || 0} total</span>
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800 shadow-xs">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs flex items-center justify-between">
                                <span>Total Requests</span>
                                <Activity className="h-3.5 w-3.5 text-emerald-500" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {stats.total_requests || 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800 shadow-xs">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs flex items-center justify-between">
                                <span>Error Rate</span>
                                <AlertCircle className="h-3.5 w-3.5 text-rose-500" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {stats.total_errors || 0}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card className="border-slate-200 dark:border-slate-800 shadow-xs">
                        <CardHeader className="pb-2">
                            <CardDescription className="text-xs flex items-center justify-between">
                                <span>Available Tools</span>
                                <Zap className="h-3.5 w-3.5 text-amber-500" />
                            </CardDescription>
                            <CardTitle className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {tools.length || 16}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Tabs Navigation */}
                <div className="flex flex-wrap items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-xl border border-slate-200 dark:border-slate-700/60 w-fit">
                    <button
                        type="button"
                        onClick={() => setActiveTab('keys')}
                        className={cn(
                            "flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all duration-150",
                            activeTab === 'keys'
                                ? "bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-400 shadow-xs font-bold"
                                : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/50"
                        )}
                    >
                        <Key className="h-4 w-4" />
                        API Keys ({keys.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('connect')}
                        className={cn(
                            "flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all duration-150",
                            activeTab === 'connect'
                                ? "bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-400 shadow-xs font-bold"
                                : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/50"
                        )}
                    >
                        <Terminal className="h-4 w-4" />
                        Panduan Koneksi AI
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('tools')}
                        className={cn(
                            "flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all duration-150",
                            activeTab === 'tools'
                                ? "bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-400 shadow-xs font-bold"
                                : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/50"
                        )}
                    >
                        <Layers className="h-4 w-4" />
                        Katalog Tools ({tools.length})
                    </button>
                    <button
                        type="button"
                        onClick={() => setActiveTab('logs')}
                        className={cn(
                            "flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition-all duration-150",
                            activeTab === 'logs'
                                ? "bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-400 shadow-xs font-bold"
                                : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:bg-white/50"
                        )}
                    >
                        <Activity className="h-4 w-4" />
                        Audit Logs
                    </button>
                </div>

                {/* Tab Contents */}
                <div className="pt-1">
                    {/* TAB 1: API KEYS */}
                    {activeTab === 'keys' && (
                        <div className="space-y-4">
                            <Card className="border-slate-200 dark:border-slate-800">
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Daftar API Key Terdaftar</CardTitle>
                                    <CardDescription className="text-xs">
                                        Token otentikasi yang digunakan oleh AI Client untuk mengeksekusi MCP tools dan query data.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader className="bg-slate-50 dark:bg-slate-900/40">
                                                <TableRow>
                                                    <TableHead>Nama Label</TableHead>
                                                    <TableHead>Client AI</TableHead>
                                                    <TableHead>Key Prefix</TableHead>
                                                    <TableHead>Status</TableHead>
                                                    <TableHead>Terakhir Dipakai</TableHead>
                                                    <TableHead className="text-right">Aksi</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {keys.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={6} className="text-center py-8 text-slate-500 text-sm">
                                                            Belum ada API Key yang dibuat. Klik tombol <strong>"Generate API Key"</strong> di atas.
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    keys.map((k) => (
                                                        <TableRow key={k.id}>
                                                            <TableCell className="font-medium text-sm">
                                                                {k.name}
                                                            </TableCell>
                                                            <TableCell>
                                                                <Badge variant="secondary" className="capitalize text-xs font-mono">
                                                                    {k.client_type}
                                                                </Badge>
                                                            </TableCell>
                                                            <TableCell>
                                                                <code className="text-xs font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                                                    {k.key_prefix}...
                                                                </code>
                                                            </TableCell>
                                                            <TableCell>
                                                                {k.is_active ? (
                                                                    <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-none text-xs">
                                                                        Aktif
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge variant="outline" className="text-slate-500 border-slate-300 text-xs">
                                                                        Dinonaktifkan
                                                                    </Badge>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-xs text-slate-500">
                                                                {k.last_used_at ? new Date(k.last_used_at).toLocaleString('id-ID') : 'Belum pernah'}
                                                            </TableCell>
                                                            <TableCell className="text-right space-x-1.5 whitespace-nowrap">
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleOpenEdit(k)}
                                                                    className="text-xs h-7 px-2.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 border-indigo-200 dark:border-indigo-800"
                                                                    title="Edit Nama Label"
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5 mr-1" />
                                                                    Edit
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleToggleKey(k.id)}
                                                                    className="text-xs h-7 px-2"
                                                                >
                                                                    {k.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                                                </Button>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleRevokeKey(k.id, k.name)}
                                                                    className="text-xs h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                                >
                                                                    <Trash2 className="h-3.5 w-3.5" />
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* TAB 2: CONNECTION GUIDES */}
                    {activeTab === 'connect' && (
                        <div className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Card ChatGPT Custom Actions */}
                                <Card className="border-slate-200 dark:border-slate-800">
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-base flex items-center gap-2">
                                                <span className="p-1 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                    🤖
                                                </span>
                                                ChatGPT (Custom GPT / Actions)
                                            </CardTitle>
                                            <Badge variant="outline" className="text-xs">OpenAPI 3.1</Badge>
                                        </div>
                                        <CardDescription className="text-xs">
                                            Gunakan URL OpenAPI berikut saat membuat Custom GPT di OpenAI GPT Builder.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div>
                                            <Label className="text-xs text-slate-500">OpenAPI Schema URL:</Label>
                                            <div className="flex items-center gap-2 mt-1">
                                                <Input
                                                    readOnly
                                                    value={endpoints.openapi}
                                                    className="text-xs font-mono bg-slate-50 dark:bg-slate-900"
                                                />
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleCopy(endpoints.openapi, 'openapi')}
                                                >
                                                    {copiedMap['openapi'] ? <Check className="h-4 w-4 text-emerald-600" /> : <Copy className="h-4 w-4" />}
                                                </Button>
                                            </div>
                                        </div>
                                        <div className="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-lg text-xs text-slate-600 dark:text-slate-400 space-y-1">
                                            <p className="font-semibold text-slate-800 dark:text-slate-200">Cara Setup di ChatGPT Builder:</p>
                                            <p>1. Di tab <em>Configure</em>, klik <strong>Create new action</strong>.</p>
                                            <p>2. Klik <em>Import from URL</em> lalu paste URL OpenAPI di atas.</p>
                                            <p>3. Di menu <em>Authentication</em>, pilih <strong>Bearer</strong> dan paste API Key Anda.</p>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Card Claude Desktop & Cursor */}
                                <Card className="border-slate-200 dark:border-slate-800">
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-base flex items-center gap-2">
                                                <span className="p-1 rounded bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                    ⚡
                                                </span>
                                                Claude Desktop & Cursor (SSE Remote)
                                            </CardTitle>
                                            <Badge variant="outline" className="text-xs">JSON-RPC / SSE</Badge>
                                        </div>
                                        <CardDescription className="text-xs">
                                            Tambahkan konfigurasi ini ke file <code className="text-xs font-mono">claude_desktop_config.json</code>.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <div>
                                            <div className="flex items-center justify-between mb-1">
                                                <Label className="text-xs text-slate-500">Config Snippet:</Label>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-6 px-2 text-xs"
                                                    onClick={() => handleCopy(claudeDesktopConfig, 'claude')}
                                                >
                                                    {copiedMap['claude'] ? <Check className="h-3 w-3 text-emerald-600 mr-1" /> : <Copy className="h-3 w-3 mr-1" />}
                                                    Salin JSON
                                                </Button>
                                            </div>
                                            <pre className="p-3 bg-slate-950 text-slate-200 rounded-lg text-xs font-mono overflow-x-auto max-h-48">
                                                {claudeDesktopConfig}
                                            </pre>
                                        </div>
                                        <p className="text-xs text-slate-500">
                                            Ganti <code className="font-mono text-indigo-500">YOUR_MCP_API_KEY</code> dengan token dari tab API Keys.
                                        </p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    )}

                    {/* TAB 3: TOOLS CATALOG */}
                    {activeTab === 'tools' && (
                        <div className="space-y-4">
                            <Card className="border-slate-200 dark:border-slate-800">
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Katalog 16 MCP Tools Terisolasi</CardTitle>
                                    <CardDescription className="text-xs">
                                        Seluruh fungsi ini dioptimasi dengan query token-dense pre-aggregated cache agar server cPanel tetap ringan dan cepat.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="divide-y divide-slate-100 dark:divide-slate-800">
                                        {tools.map((t, idx) => (
                                            <div key={idx} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors">
                                                <div className="space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <code className="text-xs font-bold font-mono text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded">
                                                            {t.name}
                                                        </code>
                                                        <Badge variant="outline" className="text-[10px] uppercase tracking-wider">
                                                            {t.category}
                                                        </Badge>
                                                        <span className="text-[10px] text-slate-400 font-mono">
                                                            {t.scope}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-slate-600 dark:text-slate-400">
                                                        {t.description}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="text-xs h-7 shrink-0"
                                                    onClick={() => handleCopy(t.name, `tool_${idx}`)}
                                                >
                                                    {copiedMap[`tool_${idx}`] ? <Check className="h-3 w-3 text-emerald-600 mr-1" /> : <Copy className="h-3 w-3 mr-1" />}
                                                    Salin Nama
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* TAB 4: AUDIT LOGS */}
                    {activeTab === 'logs' && (
                        <div className="space-y-4">
                            <Card className="border-slate-200 dark:border-slate-800">
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Live Audit Logs (100 Terakhir)</CardTitle>
                                    <CardDescription className="text-xs">
                                        Pantau setiap pemanggilan tool oleh AI, waktu respon server, dan status keberhasilan.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <Table>
                                            <TableHeader className="bg-slate-50 dark:bg-slate-900/40">
                                                <TableRow>
                                                    <TableHead>Waktu</TableHead>
                                                    <TableHead>Tool Name</TableHead>
                                                    <TableHead>Client Key</TableHead>
                                                    <TableHead>Status</TableHead>
                                                    <TableHead>Latency</TableHead>
                                                    <TableHead>IP</TableHead>
                                                    <TableHead className="text-right">Detail</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {recentLogs.length === 0 ? (
                                                    <TableRow>
                                                        <TableCell colSpan={7} className="text-center py-8 text-slate-500 text-sm">
                                                            Belum ada log interaksi AI yang terekam.
                                                        </TableCell>
                                                    </TableRow>
                                                ) : (
                                                    recentLogs.map((log) => (
                                                        <TableRow key={log.id}>
                                                            <TableCell className="text-xs text-slate-500 whitespace-nowrap">
                                                                {new Date(log.created_at).toLocaleTimeString('id-ID')}
                                                            </TableCell>
                                                            <TableCell className="font-mono text-xs font-semibold text-indigo-700 dark:text-indigo-400">
                                                                {log.tool_name}
                                                            </TableCell>
                                                            <TableCell className="text-xs">
                                                                {log.key?.name || 'Unknown / Deleted'}
                                                            </TableCell>
                                                            <TableCell>
                                                                {log.status === 'success' ? (
                                                                    <Badge className="bg-emerald-100 text-emerald-800 hover:bg-emerald-200 border-none text-[10px]">
                                                                        SUCCESS
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge className="bg-rose-100 text-rose-800 hover:bg-rose-200 border-none text-[10px]">
                                                                        ERROR
                                                                    </Badge>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="text-xs font-mono text-slate-600">
                                                                {log.execution_time_ms}ms
                                                            </TableCell>
                                                            <TableCell className="text-xs font-mono text-slate-500">
                                                                {log.ip_address || '-'}
                                                            </TableCell>
                                                            <TableCell className="text-right">
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => setSelectedLog(log)}
                                                                    className="h-6 px-2 text-xs"
                                                                >
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                </Button>
                                                            </TableCell>
                                                        </TableRow>
                                                    ))
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}
                </div>

                {/* MODAL: CREATE NEW API KEY */}
                <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                    <DialogContent className="sm:max-w-md">
                        <form onSubmit={handleCreateKey}>
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <Key className="h-5 w-5 text-indigo-600" />
                                    Generate AI MCP API Key
                                </DialogTitle>
                                <DialogDescription className="text-xs">
                                    Buat kredensial akses baru untuk menghubungkan asisten AI ke sistem garmen.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4 py-4">
                                <div>
                                    <Label htmlFor="name" className="text-xs font-medium">Label Nama Key:</Label>
                                    <Input
                                        id="name"
                                        placeholder="Contoh: ChatGPT Sales & Reminder WhatsApp"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        className="mt-1 text-sm"
                                    />
                                    {errors.name && <p className="text-rose-600 text-xs mt-1">{errors.name}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="client_type" className="text-xs font-medium">Tipe Klien AI:</Label>
                                    <Select
                                        value={data.client_type}
                                        onValueChange={(val) => setData('client_type', val)}
                                    >
                                        <SelectTrigger className="mt-1 text-sm">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="chatgpt">ChatGPT (Custom GPT / Action)</SelectItem>
                                            <SelectItem value="claude">Claude Desktop / Anthropic</SelectItem>
                                            <SelectItem value="gemini">Google Gemini / AI Studio</SelectItem>
                                            <SelectItem value="cursor">Cursor IDE / Cline</SelectItem>
                                            <SelectItem value="opencode">OpenCode / LLM Agent</SelectItem>
                                            <SelectItem value="custom">Universal / Custom Script</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Label htmlFor="rate_limit" className="text-xs font-medium">Rate Limit (Request / Menit):</Label>
                                    <Input
                                        id="rate_limit"
                                        type="number"
                                        min="10"
                                        max="300"
                                        value={data.rate_limit_per_minute}
                                        onChange={(e) => setData('rate_limit_per_minute', e.target.value)}
                                        className="mt-1 text-sm"
                                    />
                                    <p className="text-[11px] text-slate-500 mt-1">Mencegah lonjakan beban di Shared Hosting cPanel.</p>
                                </div>
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsCreateModalOpen(false)}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                >
                                    {processing ? 'Memproses...' : 'Buat Token Sekarang'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* MODAL: DETAIL LOG */}
                <Dialog open={!!selectedLog} onOpenChange={() => setSelectedLog(null)}>
                    <DialogContent className="sm:max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="text-base font-semibold flex items-center gap-2">
                                <Activity className="h-4 w-4 text-indigo-600" />
                                Detail Audit Log #{selectedLog?.id}
                            </DialogTitle>
                        </DialogHeader>

                        {selectedLog && (
                            <div className="space-y-3 text-xs">
                                <div className="grid grid-cols-2 gap-2 bg-slate-50 dark:bg-slate-900 p-3 rounded-lg">
                                    <div>
                                        <span className="text-slate-500">Tool:</span>
                                        <p className="font-semibold font-mono">{selectedLog.tool_name}</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Status:</span>
                                        <p className="font-semibold">{selectedLog.status}</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Latency:</span>
                                        <p className="font-semibold">{selectedLog.execution_time_ms} ms</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Waktu:</span>
                                        <p className="font-semibold">{new Date(selectedLog.created_at).toLocaleString('id-ID')}</p>
                                    </div>
                                </div>

                                {selectedLog.error_message && (
                                    <div className="p-2.5 rounded bg-rose-50 text-rose-700 border border-rose-200">
                                        <span className="font-semibold">Pesan Error:</span>
                                        <p className="mt-1 font-mono">{selectedLog.error_message}</p>
                                    </div>
                                )}

                                <div>
                                    <Label className="text-slate-500">Arguments JSON:</Label>
                                    <pre className="mt-1 p-2.5 bg-slate-950 text-slate-200 rounded font-mono overflow-x-auto max-h-40">
                                        {JSON.stringify(selectedLog.arguments_json, null, 2)}
                                    </pre>
                                </div>
                            </div>
                        )}

                        <DialogFooter>
                            <Button variant="outline" size="sm" onClick={() => setSelectedLog(null)}>
                                Tutup
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* MODAL: EDIT NAMA LABEL API KEY */}
                <Dialog open={!!editingKey} onOpenChange={(open) => !open && setEditingKey(null)}>
                    <DialogContent className="sm:max-w-md">
                        <form onSubmit={handleSaveEdit}>
                            <DialogHeader>
                                <DialogTitle className="text-base font-semibold flex items-center gap-2">
                                    <Pencil className="h-4 w-4 text-indigo-600" />
                                    Edit Nama Label API Key
                                </DialogTitle>
                                <DialogDescription className="text-xs">
                                    Ganti nama label API Key ini agar lebih mudah mengenali siapa atau klien mana yang menggunakan key ini.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="py-4 space-y-3">
                                <div>
                                    <Label htmlFor="edit_name" className="text-xs font-medium">Nama Label API Key:</Label>
                                    <Input
                                        id="edit_name"
                                        value={editName}
                                        onChange={(e) => setEditName(e.target.value)}
                                        required
                                        maxLength={100}
                                        placeholder="Contoh: ChatGPT Owner / Tim Marketing"
                                        className="mt-1 text-sm"
                                        autoFocus
                                    />
                                </div>
                                {editingKey && (
                                    <div className="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs text-slate-500 space-y-1">
                                        <div className="flex justify-between">
                                            <span>Client AI:</span>
                                            <span className="font-semibold capitalize text-slate-700 dark:text-slate-300">{editingKey.client_type}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Key Prefix:</span>
                                            <code className="font-mono text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1 rounded">{editingKey.key_prefix}...</code>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <DialogFooter className="gap-2 sm:gap-0">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setEditingKey(null)}
                                    disabled={isUpdating}
                                >
                                    Batal
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={isUpdating || !editName.trim()}
                                    className="bg-indigo-600 hover:bg-indigo-700 text-white"
                                >
                                    {isUpdating ? 'Menyimpan...' : 'Simpan Perubahan'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
