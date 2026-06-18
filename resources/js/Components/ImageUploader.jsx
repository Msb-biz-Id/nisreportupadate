import { useCallback, useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';
import ReactCrop, { centerCrop, makeAspectCrop, convertToPixelCrop } from 'react-image-crop';
import 'react-image-crop/dist/ReactCrop.css';
import { Upload, X, RotateCw, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

/**
 * ImageUploader — upload + crop gambar untuk Order Form.
 * Menggunakan react-image-crop untuk memungkinkan crop dinamis & seret sudut/sisi.
 * Dilengkapi fitur "Gunakan Gambar Terakhir" dan fitur zoom slider interaktif.
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
    const inputRef = useRef(null);
    const imgRef = useRef(null);
    const [src, setSrc] = useState(null); // base64 sumber untuk di-crop
    const [open, setOpen] = useState(false); // modal crop terbuka?
    const [crop, setCrop] = useState(null); // react-image-crop state
    const [croppedPx, setCroppedPx] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [imageAspect, setImageAspect] = useState(aspect);
    const [cropAspect, setCropAspect] = useState(aspect);
    const [zoom, setZoom] = useState(1); // Zoom level state

    // State untuk memantau gambar terakhir yang diunggah di sesi ini secara reaktif
    const [lastImage, setLastImage] = useState(window.__lastUploadedImageSrc || null);

    useEffect(() => {
        const handleUpdate = () => {
            setLastImage(window.__lastUploadedImageSrc || null);
        };
        window.addEventListener('last-image-updated', handleUpdate);
        return () => window.removeEventListener('last-image-updated', handleUpdate);
    }, []);

    const onCropComplete = useCallback((c) => {
        if (imgRef.current && c && c.width > 0 && c.height > 0) {
            const pixelCrop = convertToPixelCrop(c, imgRef.current.width, imgRef.current.height);
            const scaleX = imgRef.current.naturalWidth / imgRef.current.width;
            const scaleY = imgRef.current.naturalHeight / imgRef.current.height;
            setCroppedPx({
                x: pixelCrop.x * scaleX,
                y: pixelCrop.y * scaleY,
                width: pixelCrop.width * scaleX,
                height: pixelCrop.height * scaleY
            });
        }
    }, []);

    // Recalculate crop area when zoom changes to keep coordinates aligned
    useEffect(() => {
        if (imgRef.current && crop) {
            onCropComplete(crop);
        }
    }, [zoom, crop, onCropComplete]);

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
                const resizedSrc = resizeImageIfNeeded(img, 1600);
                
                // Simpan secara global di window agar bisa digunakan kembali oleh uploader lain tanpa upload ulang
                window.__lastUploadedImageSrc = resizedSrc;
                window.__lastUploadedImageName = file.name;
                window.dispatchEvent(new CustomEvent('last-image-updated'));

                const imgTemp = new Image();
                imgTemp.onload = () => {
                    const calculatedAspect = imgTemp.width / imgTemp.height;
                    setSrc(resizedSrc);
                    setImageAspect(calculatedAspect);
                    setCropAspect(aspect); // Mulai dengan aspect prop yang diinginkan
                    setCroppedPx(null);
                    setZoom(1);
                    setOpen(true);
                };
                imgTemp.src = resizedSrc;
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
        e.target.value = ''; // izinkan pilih file yang sama lagi
    }

    function closeCrop() {
        setOpen(false);
        setSrc(null);
        setCrop(null);
        setCroppedPx(null);
        setZoom(1);
    }

    // Mengubah aspek rasio crop secara dinamis
    useEffect(() => {
        if (imgRef.current && open) {
            const { width, height } = imgRef.current;
            if (cropAspect) {
                const newCrop = centerAspectCrop(width, height, cropAspect);
                setCrop(newCrop);
                onCropComplete(newCrop);
            } else {
                const newCrop = centerFreeCrop(width, height);
                setCrop(newCrop);
                onCropComplete(newCrop);
            }
        }
    }, [cropAspect, open]);

    // Putar gambar 90 derajat searah jarum jam secara langsung pada base64 source
    async function handleRotate() {
        if (!src) return;
        try {
            const rotatedBase64 = await rotateImageBase64(src);
            setSrc(rotatedBase64);
            
            // Tukar aspek rasio gambar asli
            const nextImgAspect = 1 / imageAspect;
            setImageAspect(nextImgAspect);

            // Jika ada rasio aktif (selain bebas/null), sesuaikan rasionya
            if (cropAspect) {
                const nextCropAspect = 1 / cropAspect;
                setCropAspect(nextCropAspect);
            } else {
                // Jika bebas, recalculate crop box dengan dimensi baru
                setTimeout(() => {
                    if (imgRef.current) {
                        const { width, height } = imgRef.current;
                        const newCrop = centerFreeCrop(width, height);
                        setCrop(newCrop);
                        onCropComplete(newCrop);
                    }
                }, 50);
            }
        } catch (err) {
            toast.error('Gagal memutar gambar');
        }
    }

    async function applyAndUpload() {
        if (!croppedPx || !src) return;
        setUploading(true);
        try {
            const blob = await getCroppedBlob(src, croppedPx);
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

    function onImageLoad(e) {
        setZoom(1);
        const { width, height } = e.currentTarget;
        if (cropAspect) {
            const newCrop = centerAspectCrop(width, height, cropAspect);
            setCrop(newCrop);
            onCropComplete(newCrop);
        } else {
            const newCrop = centerFreeCrop(width, height);
            setCrop(newCrop);
            onCropComplete(newCrop);
        }
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
                <div className="flex flex-col gap-2 w-full">
                    <button
                        type="button"
                        onClick={pickFile}
                        className="flex w-full min-h-[140px] flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 text-sm text-slate-400 transition hover:border-slate-400 hover:bg-slate-100 hover:text-slate-500"
                    >
                        <Upload className="h-8 w-8 opacity-50" />
                        {label && <span className="text-xs font-semibold uppercase tracking-wider text-slate-500">{label}</span>}
                        <span className="text-[11px]">Klik untuk pilih gambar · JPG/PNG/WEBP</span>
                    </button>
                    {lastImage && (
                        <button
                            type="button"
                            onClick={() => {
                                const img = new Image();
                                img.onload = () => {
                                    const calculatedAspect = img.width / img.height;
                                    setSrc(lastImage);
                                    setImageAspect(calculatedAspect);
                                    setCropAspect(aspect);
                                    setCroppedPx(null);
                                    setOpen(true);
                                };
                                img.src = lastImage;
                            }}
                            className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-bold text-slate-700 hover:bg-slate-100 transition shadow-sm text-left w-full"
                        >
                            <img
                                src={lastImage}
                                alt="Last uploaded thumbnail"
                                className="w-8 h-8 rounded object-cover border border-slate-200 bg-white flex-shrink-0"
                            />
                            <div className="flex-1 min-w-0">
                                <p className="text-[10px] text-slate-500 font-medium leading-none mb-0.5">Gunakan Gambar Terakhir</p>
                                <p className="text-[11px] text-slate-800 font-bold truncate leading-tight">
                                    {window.__lastUploadedImageName || 'Gambar Sesi Ini'}
                                </p>
                            </div>
                        </button>
                    )}
                </div>
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

            {/* ── CROP MODAL via Portal (full-screen) ── */}
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
                                <h3 className="font-black uppercase tracking-wider text-[14px]">Potong Gambar</h3>
                                <p className="text-[11px] text-slate-400 mt-0.5">Tarik garis putus-putus atau sudut kotak untuk menentukan area potong secara bebas</p>
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
                        <div className="relative flex-1 bg-slate-100 flex items-center justify-center p-4 overflow-auto" style={{ minHeight: '320px', maxHeight: '55vh' }}>
                            {src && (
                                <ReactCrop
                                    crop={crop}
                                    onChange={(c) => setCrop(c)}
                                    onComplete={onCropComplete}
                                    aspect={cropAspect || undefined}
                                    className="max-w-full max-h-full"
                                >
                                    <img
                                        ref={imgRef}
                                        src={src}
                                        alt="Crop Target"
                                        onLoad={onImageLoad}
                                        style={{
                                            width: zoom > 1 ? `${zoom * 100}%` : '100%',
                                            maxWidth: zoom > 1 ? 'none' : '100%',
                                            maxHeight: zoom > 1 ? 'none' : '50vh',
                                        }}
                                        className="object-contain select-none"
                                        crossOrigin="anonymous"
                                    />
                                </ReactCrop>
                            )}
                        </div>

                        {/* Controls */}
                        <div className="bg-white px-5 py-4 space-y-4 border-t border-slate-100">
                            {/* Zoom Slider */}
                            <div className="flex flex-col gap-1.5">
                                <div className="flex justify-between items-center">
                                    <span className="text-[11px] font-black uppercase tracking-wider text-slate-500">Perbesar (Zoom)</span>
                                    <span className="text-xs font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded">{zoom.toFixed(1)}x</span>
                                </div>
                                <input
                                    type="range"
                                    min="1"
                                    max="3"
                                    step="0.1"
                                    value={zoom}
                                    onChange={(e) => setZoom(parseFloat(e.target.value))}
                                    className="h-1.5 w-full cursor-pointer appearance-none rounded-lg bg-slate-200 accent-slate-800"
                                />
                            </div>

                            {/* Aspect Ratio Presets */}
                            <div className="flex flex-col gap-2">
                                <span className="text-[11px] font-black uppercase tracking-wider text-slate-500">Rasio Potong</span>
                                <div className="flex flex-wrap gap-1.5">
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(null)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            cropAspect === null
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Bebas (Dinamis)
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setCropAspect(imageAspect)}
                                        className={cn(
                                            "rounded px-3 py-1.5 text-xs font-bold transition border",
                                            cropAspect !== null && Math.abs(cropAspect - imageAspect) < 0.01
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
                                            cropAspect !== null && Math.abs(cropAspect - 1) < 0.01
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
                                            cropAspect !== null && Math.abs(cropAspect - 4 / 3) < 0.01
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
                                            cropAspect !== null && Math.abs(cropAspect - 16 / 9) < 0.01
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
                                            cropAspect !== null && Math.abs(cropAspect - 2 / 3) < 0.01
                                                ? "bg-slate-800 text-white border-slate-800"
                                                : "bg-white text-slate-600 border-slate-200 hover:bg-slate-50"
                                        )}
                                    >
                                        Tinggi (2:3)
                                    </button>
                                </div>
                            </div>

                            {/* Rotation + Actions */}
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={handleRotate}
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

function centerAspectCrop(mediaWidth, mediaHeight, aspect) {
    return centerCrop(
        makeAspectCrop(
            {
                unit: '%',
                width: 90,
            },
            aspect,
            mediaWidth,
            mediaHeight
        ),
        mediaWidth,
        mediaHeight
    );
}

function centerFreeCrop(mediaWidth, mediaHeight) {
    return centerCrop(
        {
            unit: '%',
            width: 90,
            height: 90,
        },
        mediaWidth,
        mediaHeight
    );
}

/**
 * Hasilkan Blob JPEG dari area crop canvas.
 */
async function getCroppedBlob(imageSrc, areaPx) {
    const image = await loadImage(imageSrc);
    const cropCanvas = document.createElement('canvas');
    cropCanvas.width = areaPx.width;
    cropCanvas.height = areaPx.height;
    const cropCtx = cropCanvas.getContext('2d');

    cropCtx.drawImage(
        image,
        areaPx.x, areaPx.y, areaPx.width, areaPx.height, // sumber
        0, 0, areaPx.width, areaPx.height // tujuan
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
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

function resizeImageIfNeeded(img, maxDim = 1600) {
    if (img.width <= maxDim && img.height <= maxDim) {
        return img.src;
    }
    const canvas = document.createElement('canvas');
    let width = img.width;
    let height = img.height;
    if (width > height) {
        if (width > maxDim) {
            height = Math.round((height * maxDim) / width);
            width = maxDim;
        }
    } else {
        if (height > maxDim) {
            width = Math.round((width * maxDim) / height);
            height = maxDim;
        }
    }
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0, width, height);
    return canvas.toDataURL('image/jpeg', 0.92);
}

async function rotateImageBase64(imageSrc) {
    const image = await loadImage(imageSrc);
    const canvas = document.createElement('canvas');
    
    // Swap width and height for 90 deg rotation
    canvas.width = image.height;
    canvas.height = image.width;
    const ctx = canvas.getContext('2d');
    
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate((90 * Math.PI) / 180);
    ctx.translate(-image.width / 2, -image.height / 2);
    ctx.drawImage(image, 0, 0);
    
    return canvas.toDataURL('image/jpeg', 0.92);
}
