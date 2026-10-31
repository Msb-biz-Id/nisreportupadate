import React, { useState, useEffect, useMemo, useRef } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    ZoomIn,
    ZoomOut,
    RotateCw,
    Maximize2,
    Minimize2,
    ExternalLink,
    Download,
    RefreshCw,
    FileText,
    Image as ImageIcon,
    Cloud,
    AlertCircle,
    X,
} from 'lucide-react';

export default function UniversalDocumentViewer({
    isOpen,
    onClose,
    fileUrl,
    fileName = 'Dokumen Digital',
    fileType = 'auto',
}) {
    const [zoom, setZoom] = useState(1);
    const [rotation, setRotation] = useState(0);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [hasError, setHasError] = useState(false);
    const containerRef = useRef(null);

    // Reset state saat modal dibuka atau URL berganti
    useEffect(() => {
        if (isOpen) {
            setZoom(1);
            setRotation(0);
            setIsLoading(true);
            setHasError(false);
        }
    }, [isOpen, fileUrl]);

    // Ekstrak dan normalisasi URL berkas
    const parsedFile = useMemo(() => {
        if (!fileUrl) {
            return {
                embedUrl: '',
                viewUrl: '',
                downloadUrl: '',
                isGoogleDrive: false,
                isPdf: false,
                isImage: false,
                driveId: null,
            };
        }

        const rawUrl = String(fileUrl).trim();
        let driveId = null;

        // Cek format Google Drive
        const gdriveRegex1 = /drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]+)/i;
        const gdriveRegex2 = /[?&]id=([a-zA-Z0-9_-]+)/i;

        const match1 = rawUrl.match(gdriveRegex1);
        const match2 = rawUrl.match(gdriveRegex2);

        if (match1 && match1[1]) {
            driveId = match1[1];
        } else if (rawUrl.includes('drive.google.com') && match2 && match2[1]) {
            driveId = match2[1];
        } else if (rawUrl.startsWith('gdrive:')) {
            driveId = rawUrl.replace('gdrive:', '');
        }

        const isGoogleDrive = Boolean(driveId);
        const cleanLower = rawUrl.toLowerCase().split('?')[0];

        let isPdf = fileType === 'pdf' || cleanLower.endsWith('.pdf') || rawUrl.includes('/pdf') || isGoogleDrive;
        let isImage = fileType === 'image' || cleanLower.endsWith('.jpg') || cleanLower.endsWith('.jpeg') || cleanLower.endsWith('.png') || cleanLower.endsWith('.webp') || cleanLower.endsWith('.gif');

        if (isImage) {
            isPdf = false;
        }

        let embedUrl = rawUrl;
        let viewUrl = rawUrl;
        let downloadUrl = rawUrl;

        if (isGoogleDrive && driveId) {
            embedUrl = `https://drive.google.com/file/d/${driveId}/preview`;
            viewUrl = `https://drive.google.com/file/d/${driveId}/view?usp=sharing`;
            downloadUrl = `https://drive.google.com/uc?id=${driveId}&export=download`;
        } else if (isPdf && !rawUrl.includes('#')) {
            embedUrl = `${rawUrl}#toolbar=1&navpanes=0`;
        }

        return {
            embedUrl,
            viewUrl,
            downloadUrl,
            isGoogleDrive,
            isPdf,
            isImage,
            driveId,
        };
    }, [fileUrl, fileType]);

    // Handle Zoom
    const handleZoomIn = () => setZoom((prev) => Math.min(prev + 0.25, 2.5));
    const handleZoomOut = () => setZoom((prev) => Math.max(prev - 0.25, 0.5));
    const handleResetView = () => {
        setZoom(1);
        setRotation(0);
    };

    // Handle Rotate
    const handleRotate = () => setRotation((prev) => (prev + 90) % 360);

    // Handle Fullscreen
    const toggleFullscreen = () => {
        if (!containerRef.current) return;

        if (!document.fullscreenElement) {
            containerRef.current.requestFullscreen().then(() => setIsFullscreen(true)).catch(() => {});
        } else {
            document.exitFullscreen().then(() => setIsFullscreen(false)).catch(() => {});
        }
    };

    if (!isOpen) return null;

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent
                className={`p-0 gap-0 overflow-hidden flex flex-col transition-all duration-200 border-zinc-200 dark:border-zinc-800 ${
                    isFullscreen
                        ? 'max-w-[100vw] w-screen h-screen rounded-none'
                        : 'max-w-5xl w-[94vw] h-[88vh] max-h-[92vh] rounded-xl shadow-2xl'
                }`}
                ref={containerRef}
            >
                {/* TOOLBAR HEADER */}
                <div className="flex flex-wrap items-center justify-between gap-3 px-4 py-3 bg-zinc-900 text-zinc-100 border-b border-zinc-800 select-none">
                    {/* Judul & Tipe Berkas */}
                    <div className="flex items-center gap-2.5 min-w-0">
                        <div className="p-1.5 rounded bg-zinc-800 text-zinc-200">
                            {parsedFile.isGoogleDrive ? (
                                <Cloud className="h-4 w-4 text-emerald-400" />
                            ) : parsedFile.isImage ? (
                                <ImageIcon className="h-4 w-4 text-blue-400" />
                            ) : (
                                <FileText className="h-4 w-4 text-amber-400" />
                            )}
                        </div>
                        <div className="min-w-0">
                            <h3 className="text-sm font-semibold truncate text-zinc-100 max-w-[240px] sm:max-w-md" title={fileName}>
                                {fileName}
                            </h3>
                            <div className="flex items-center gap-2 mt-0.5 text-[11px] text-zinc-400">
                                {parsedFile.isGoogleDrive && (
                                    <Badge variant="outline" className="border-emerald-600/50 bg-emerald-950/40 text-emerald-300 text-[10px] px-1.5 py-0 font-normal">
                                        Google Drive
                                    </Badge>
                                )}
                                <span>{parsedFile.isImage ? 'Gambar Scan' : 'Dokumen PDF / Web'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Kontrol Aksi: Zoom, Putar, Layar Penuh, Buka Tab Baru */}
                    <div className="flex items-center gap-1 sm:gap-1.5 ml-auto">
                        {/* Zoom Controls (Hanya untuk gambar atau iframe scale) */}
                        {parsedFile.isImage && (
                            <div className="flex items-center bg-zinc-800/80 rounded-md p-0.5 mr-1 border border-zinc-700/50">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleZoomOut}
                                    title="Perkecil (-)"
                                    className="h-7 w-7 text-zinc-300 hover:text-white hover:bg-zinc-700"
                                >
                                    <ZoomOut className="h-3.5 w-3.5" />
                                </Button>
                                <span className="px-1.5 text-[11px] font-mono text-zinc-300 min-w-11 text-center">
                                    {Math.round(zoom * 100)}%
                                </span>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleZoomIn}
                                    title="Perbesar (+)"
                                    className="h-7 w-7 text-zinc-300 hover:text-white hover:bg-zinc-700"
                                >
                                    <ZoomIn className="h-3.5 w-3.5" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleRotate}
                                    title="Putar 90 Derajat"
                                    className="h-7 w-7 text-zinc-300 hover:text-white hover:bg-zinc-700 ml-0.5"
                                >
                                    <RotateCw className="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        )}

                        {/* Buka di Tab Baru / Google Drive */}
                        {parsedFile.viewUrl && (
                            <a
                                href={parsedFile.viewUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white transition border border-zinc-700"
                                title="Buka tautan asli di tab browser baru"
                            >
                                <ExternalLink className="h-3.5 w-3.5" />
                                <span className="hidden sm:inline">
                                    {parsedFile.isGoogleDrive ? 'Buka di Google Drive' : 'Tab Baru'}
                                </span>
                            </a>
                        )}

                        {/* Unduh Berkas */}
                        {parsedFile.downloadUrl && (
                            <a
                                href={parsedFile.downloadUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                download
                                className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md bg-zinc-800 hover:bg-zinc-700 text-zinc-200 hover:text-white transition border border-zinc-700"
                                title="Unduh berkas ke komputer lokal"
                            >
                                <Download className="h-3.5 w-3.5" />
                                <span className="hidden sm:inline">Unduh</span>
                            </a>
                        )}

                        {/* Toggle Layar Penuh */}
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={toggleFullscreen}
                            className="h-8 w-8 text-zinc-300 hover:text-white hover:bg-zinc-800"
                            title={isFullscreen ? 'Keluar Layar Penuh' : 'Layar Penuh'}
                        >
                            {isFullscreen ? <Minimize2 className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
                        </Button>

                        {/* Tutup */}
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            onClick={onClose}
                            className="h-8 w-8 text-zinc-400 hover:text-white hover:bg-red-950/40 hover:text-red-400 ml-1"
                            title="Tutup Modal (Esc)"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* VIEWPORT CANVAS */}
                <div className="flex-1 w-full relative bg-zinc-950 flex items-center justify-center overflow-auto p-2">
                    {/* Loading State */}
                    {isLoading && (
                        <div className="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950/80 z-20 backdrop-blur-[2px] text-zinc-400 gap-3">
                            <RefreshCw className="h-7 w-7 animate-spin text-red-500" />
                            <span className="text-xs tracking-wide">Memuat pratinjau dokumen...</span>
                        </div>
                    )}

                    {/* Error State */}
                    {hasError && (
                        <div className="flex flex-col items-center justify-center p-8 max-w-md text-center bg-zinc-900 border border-zinc-800 rounded-xl space-y-4 z-10">
                            <div className="p-3 bg-red-950/50 text-red-400 rounded-full">
                                <AlertCircle className="h-8 w-8" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-zinc-200">Gagal Menampilkan Pratinjau</h4>
                                <p className="text-xs text-zinc-400 mt-1">
                                    Berkas mungkin memiliki proteksi akses privat atau browser memblokir frame embed Google.
                                </p>
                            </div>
                            <div className="flex gap-2">
                                {parsedFile.viewUrl && (
                                    <a
                                        href={parsedFile.viewUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white transition"
                                    >
                                        <ExternalLink className="h-3.5 w-3.5" />
                                        Buka Langsung di Tab Baru
                                    </a>
                                )}
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        setHasError(false);
                                        setIsLoading(true);
                                    }}
                                    className="border-zinc-700 text-zinc-300"
                                >
                                    Coba Lagi
                                </Button>
                            </div>
                        </div>
                    )}

                    {/* CONTENT RENDER: GAMBAR ATAU IFRAME */}
                    {!hasError && parsedFile.embedUrl && (
                        parsedFile.isImage ? (
                            <div className="w-full h-full flex items-center justify-center overflow-auto p-4">
                                <img
                                    src={parsedFile.embedUrl}
                                    alt={fileName}
                                    style={{
                                        transform: `scale(${zoom}) rotate(${rotation}deg)`,
                                        transformOrigin: 'center center',
                                        transition: 'transform 0.2s ease-in-out',
                                    }}
                                    className="max-h-[75vh] max-w-full object-contain rounded shadow-lg"
                                    onLoad={() => setIsLoading(false)}
                                    onError={() => {
                                        setIsLoading(false);
                                        setHasError(true);
                                    }}
                                />
                            </div>
                        ) : (
                            <iframe
                                src={parsedFile.embedUrl}
                                title={fileName}
                                className="w-full h-full min-h-[60vh] border-0 rounded bg-white shadow"
                                allow="autoplay; encrypted-media"
                                onLoad={() => setIsLoading(false)}
                                onError={() => {
                                    setIsLoading(false);
                                    setHasError(true);
                                }}
                            />
                        )
                    )}

                    {/* Fallback Jika URL Kosong */}
                    {!parsedFile.embedUrl && !isLoading && (
                        <div className="text-zinc-500 text-xs">
                            Tautan berkas digital tidak tersedia.
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
