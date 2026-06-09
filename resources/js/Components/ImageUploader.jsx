import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';
import Cropper from 'react-easy-crop';
import { Upload, X, RotateCw, Loader2, ZoomIn, ZoomOut, ImageOff } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

/**
 * ImageUploader — upload + crop gambar untuk Order Form.
 *
 * Props:
 *   value      : string | null  — path tersimpan (relatif storage/app/public)
 *   onChange   : (path|null) => void
 *   purpose    : 'products' | 'orders' | 'brands'
 *   aspect     : number (default 4/3)
 *   namaPo     : string | null — untuk subfolder per-PO
 *   label      : string — label kecil di atas drop zone
 *   className  : string
 */
export default function ImageUploader({
    value,
    onChange,
    purpose = 'orders',
    aspect = 4 / 3,
    namaPo = null,
    label = '',
    className,
}) {
    const inputRef  = useRef(null);
    const [src, setSrc]         = useState(null);   // base64 sumber untuk di-crop
    const [open, setOpen]       = useState(false);  // modal crop terbuka?
    const [crop, setCrop]       = useState({ x: 0, y: 0 });
    const [zoom, setZoom]       = useState(1);
    const [rotation, setRotation] = useState(0);
    const [croppedPx, setCroppedPx] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [imageAspect, setImageAspect] = useState(aspect);
    const [cropAspect, setCropAspect] = useState(aspect);

    const onCropComplete = useCallback((_, px) => setCroppedPx(px), []);

    // Tutup modal dengan Escape
    useEffect(() => {
        if (!open) return;
        const handler = (e) => { if (e.key === 'Escape' && !uploading) closeCrop(); };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [open, uploading]);

    function pickFile() { inputRef.current?.click(); }

    function handleFileChange(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Maksimal 5 MB');
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                const calculatedAspect = img.width / img.height;
                setSrc(reader.result);
                setImageAspect(calculatedAspect);
                setCropAspect(calculatedAspect); // Default to full area/original aspect ratio
                setCrop({ x: 0, y: 0 });
                setZoom(1);
                setRotation(0);
                setCroppedPx(null);
                setOpen(true);
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
        e.target.value = ''; // izinkan pilih file yang sama lagi
    }

    function closeCrop() {
        setOpen(false);
        setSrc(null);
    }

    async function applyAndUpload() {
        if (!croppedPx || !src) return;
        setUploading(true);
        try {
            const blob = await getCroppedBlob(src, croppedPx, rotation);
            const fd = new FormData();
            fd.append('file', blob, `crop-${Date.now()}.jpg`);
            fd.append('purpose', purpose);
            if (namaPo) fd.append('nama_po', namaPo);

            const { data } = await axios.post(route('uploads.image'), fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            // hapus file lama jika ada
            if (value) {
                axios.delete(route('uploads.image.destroy'), { data: { path: value } }).catch(() => {});
            }

            onChange(data.path);
            toast.success('Gambar berhasil diunggah');
            closeCrop();
        } catch (err) {
            toast.error(err?.response?.data?.message || 'Gagal upload');
        } finally {
            setUploading(false);
        }
    }

    function removeImage() {
        if (!value) return;
        if (!confirm('Hapus gambar ini?')) return;
        axios.delete(route('uploads.image.destroy'), { data: { path: value } })
            .then(() => { onChange(null); toast.success('Gambar dihapus'); })
            .catch(() => toast.error('Gagal menghapus'));
    }

    const previewUrl = value ? `/storage/${value}` : null;

    return (
        <div className={cn('w-full', className)}>
            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={handleFileChange}
                className="hidden"
            />

            {/* ── DROP ZONE / PREVIEW ── */}
            {!previewUrl ? (
                <button
                    type="button"
                    onClick={pickFile}
                    className="flex w-full min-h-[160px] flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-sm text-slate-400 transition hover:border-slate-400 hover:bg-slate-100 hover:text-slate-500"
                >
                    <Upload className="h-8 w-8 opacity-50" />
                    {label && <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</span>}
                    <span className="text-[11px]">Klik untuk pilih gambar · JPG/PNG/WEBP · maks 5 MB</span>
                </button>
            ) : (
                <div className="group relative w-full overflow-hidden rounded-lg border-2 border-slate-200 bg-slate-50">
                    <img
                        src={previewUrl}
                        alt="Preview"
                        className="w-full object-contain"
                        style={{ maxHeight: '200px' }}
                    />
                    {/* overlay buttons */}
                    <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100">
                        <button
                            type="button"
                            onClick={pickFile}
                            className="flex items-center gap-1 rounded-md bg-white/90 px-3 py-1.5 text-xs font-bold text-slate-800 shadow hover:bg-white"
                        >
                            <RotateCw className="h-3.5 w-3.5" /> Ganti
                        </button>
                        <button
                            type="button"
                            onClick={removeImage}
                            className="flex items-center gap-1 rounded-md bg-red-500/90 px-3 py-1.5 text-xs font-bold text-white shadow hover:bg-red-600"
                        >
                            <X className="h-3.5 w-3.5" /> Hapus
                        </button>
                    </div>
                </div>
            )}

            {/* ── CROP MODAL via Portal (full-screen, no Dialog issues) ── */}
            {open && createPortal(
                <div
                    className="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-slate-900/95 p-4 md:p-8"
                    style={{ backdropFilter: 'blur(4px)' }}
                >
                    <div className="flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                         style={{ maxHeight: '90vh' }}>

                        {/* Header */}
                        <div className="flex items-center justify-between bg-slate-800 px-5 py-4 text-white">
                            <div>
                                <h3 className="font-black uppercase tracking-wider">Potong Gambar</h3>
                                <p className="text-xs text-slate-400 mt-0.5">Geser & zoom untuk menentukan area potong</p>
                            </div>
                            <button
                                type="button"
                                onClick={closeCrop}
                                disabled={uploading}
                                className="rounded-lg p-2 text-slate-400 hover:bg-slate-700 hover:text-white transition"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Crop Area */}
                        <div className="relative flex-1 bg-slate-100" style={{ minHeight: '320px', maxHeight: '55vh' }}>
                            {src && (
                                <Cropper
                                    image={src}
                                    crop={crop}
                                    zoom={zoom}
                                    rotation={rotation}
                                    aspect={cropAspect}
                                    onCropChange={setCrop}
                                    onZoomChange={setZoom}
                                    onRotationChange={setRotation}
                                    onCropComplete={onCropComplete}
                                    showGrid={true}
                                    objectFit="contain"
                                />
                            )}
                        </div>

                        {/* Controls */}
                        <div className="bg-white px-5 py-4 space-y-4 border-t border-slate-100">
                            {/* Aspect Ratio Presets */}
                            <div className="flex flex-col gap-2">
                                <span className="text-[11px] font-black uppercase tracking-wider text-slate-500">Rasio Potong (Bentuk Kotak)</span>
                                <div className="flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(imageAspect)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            Math.abs(cropAspect - imageAspect) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Asli / Full Area ({imageAspect.toFixed(2)})
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(1)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            Math.abs(cropAspect - 1) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Kotak (1:1)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(4 / 3)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            Math.abs(cropAspect - 4/3) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Foto (4:3)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(16 / 9)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            Math.abs(cropAspect - 16/9) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Lebar (16:9)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(2 / 3)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            Math.abs(cropAspect - 2/3) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Tinggi (2:3)
                                    </button>
                                </div>
                            </div>

                            {/* Aspect Ratio Slider & Zoom Slider */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                {/* Aspect Ratio Slider */}
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-black uppercase tracking-wider text-slate-500">Rasio Bebas (Geser Bebas)</span>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="range"
                                            min={0.3}
                                            max={3.0}
                                            step={0.05}
                                            value={cropAspect}
                                            onChange={(e) => setCropAspect(Number(e.target.value))}
                                            className="flex-1 accent-slate-700 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer"
                                        />
                                        <span className="w-12 text-right text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{cropAspect.toFixed(2)}:1</span>
                                    </div>
                                </div>

                                {/* Zoom */}
                                <div className="flex flex-col gap-1.5">
                                    <span className="text-[11px] font-black uppercase tracking-wider text-slate-500">Perbesar (Zoom)</span>
                                    <div className="flex items-center gap-3">
                                        <ZoomOut className="h-4 w-4 text-slate-400 shrink-0" />
                                        <input
                                            type="range"
                                            min={1}
                                            max={3}
                                            step={0.05}
                                            value={zoom}
                                            onChange={(e) => setZoom(Number(e.target.value))}
                                            className="flex-1 accent-slate-700 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer"
                                        />
                                        <ZoomIn className="h-4 w-4 text-slate-400 shrink-0" />
                                        <span className="w-12 text-right text-xs font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{zoom.toFixed(1)}×</span>
                                    </div>
                                </div>
                            </div>

                            {/* Rotation + Actions */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setRotation((r) => (r + 90) % 360)}
                                    className="flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                                >
                                    <RotateCw className="h-4 w-4" /> Putar 90°
                                </button>
                                <div className="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        onClick={closeCrop}
                                        disabled={uploading}
                                        className="rounded-lg border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        onClick={applyAndUpload}
                                        disabled={uploading}
                                        className="flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-700 transition disabled:opacity-60"
                                    >
                                        {uploading
                                            ? <><Loader2 className="h-4 w-4 animate-spin" /> Mengunggah…</>
                                            : <><Upload className="h-4 w-4" /> Terapkan & Unggah</>
                                        }
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>,
                document.body,
            )}
        </div>
    );
}

/* ─── Helpers ─────────────────────────────────────────────────────────────── */

/**
 * Hasilkan Blob JPEG dari area crop + rotasi.
 * Menggunakan 2 canvas terpisah untuk menghindari masalah reset context.
 */
async function getCroppedBlob(imageSrc, areaPx, rotation = 0) {
    const image = await loadImage(imageSrc);

    // Canvas 1: gambar penuh setelah dirotasi (ukuran "safe area")
    const safeArea = Math.ceil(Math.max(image.width, image.height) * Math.SQRT2);
    const rotCanvas = document.createElement('canvas');
    rotCanvas.width  = safeArea;
    rotCanvas.height = safeArea;
    const rotCtx = rotCanvas.getContext('2d');

    rotCtx.translate(safeArea / 2, safeArea / 2);
    rotCtx.rotate((rotation * Math.PI) / 180);
    rotCtx.translate(-image.width / 2, -image.height / 2);
    rotCtx.drawImage(image, 0, 0);

    // Canvas 2: area crop dari canvas 1
    const cropCanvas = document.createElement('canvas');
    cropCanvas.width  = areaPx.width;
    cropCanvas.height = areaPx.height;
    const cropCtx = cropCanvas.getContext('2d');

    // Koordinat sumber dalam rotCanvas: pusat gambar di (safeArea/2, safeArea/2)
    const sx = Math.round(safeArea / 2 - image.width / 2 + areaPx.x);
    const sy = Math.round(safeArea / 2 - image.height / 2 + areaPx.y);

    cropCtx.drawImage(
        rotCanvas,
        sx, sy, areaPx.width, areaPx.height,   // sumber
        0, 0, areaPx.width, areaPx.height,      // tujuan
    );

    return new Promise((resolve, reject) =>
        cropCanvas.toBlob(
            (blob) => blob ? resolve(blob) : reject(new Error('Canvas toBlob gagal')),
            'image/jpeg',
            0.92,
        ),
    );
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload  = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}
