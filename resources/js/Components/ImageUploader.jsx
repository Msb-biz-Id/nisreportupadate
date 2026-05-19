import { useCallback, useRef, useState } from 'react';
import axios from 'axios';
import Cropper from 'react-easy-crop';
import { Upload, X, Crop as CropIcon, Loader2, RotateCw } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { cn } from '@/lib/utils';

/**
 * Reusable image uploader dengan drag-drop, crop modal (react-easy-crop), preview.
 *
 * Props:
 *  value: string | null - path stored di DB (relatif terhadap storage/app/public)
 *  onChange: (path|null) => void
 *  purpose: 'products' | 'orders' | 'brands' - sub-folder di storage
 *  aspect: number - aspect ratio crop, default 1
 *  className: string
 */
export default function ImageUploader({ value, onChange, purpose = 'products', aspect = 1, className }) {
    const inputRef = useRef(null);
    const [sourceImage, setSourceImage] = useState(null);
    const [cropOpen, setCropOpen] = useState(false);
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [rotation, setRotation] = useState(0);
    const [croppedAreaPixels, setCroppedAreaPixels] = useState(null);
    const [uploading, setUploading] = useState(false);

    const onCropComplete = useCallback((_, areaPx) => setCroppedAreaPixels(areaPx), []);

    function pickFile() {
        inputRef.current?.click();
    }

    function handleFileSelect(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            toast.error('Ukuran file maksimal 5MB');
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            setSourceImage(reader.result);
            setCropOpen(true);
            setCrop({ x: 0, y: 0 });
            setZoom(1);
            setRotation(0);
        };
        reader.readAsDataURL(file);
        e.target.value = ''; // reset agar file yang sama bisa dipilih lagi
    }

    async function uploadCropped() {
        if (!croppedAreaPixels || !sourceImage) return;
        setUploading(true);
        try {
            const blob = await getCroppedBlob(sourceImage, croppedAreaPixels, rotation);
            const form = new FormData();
            form.append('file', blob, `crop-${Date.now()}.jpg`);
            form.append('purpose', purpose);

            const { data } = await axios.post(route('uploads.image'), form, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });

            // Hapus file lama jika ada
            if (value) {
                axios.delete(route('uploads.image.destroy'), { data: { path: value } }).catch(() => {});
            }

            onChange(data.path);
            toast.success('Gambar berhasil diunggah');
            setCropOpen(false);
            setSourceImage(null);
        } catch (err) {
            toast.error(err?.response?.data?.message || 'Upload gagal');
        } finally {
            setUploading(false);
        }
    }

    function removeImage() {
        if (!value) return;
        if (!confirm('Hapus gambar?')) return;
        axios.delete(route('uploads.image.destroy'), { data: { path: value } })
            .then(() => {
                onChange(null);
                toast.success('Gambar dihapus');
            })
            .catch(() => toast.error('Gagal menghapus'));
    }

    const previewUrl = value ? `/storage/${value}` : null;

    return (
        <div className={cn('space-y-2', className)}>
            <input ref={inputRef} type="file" accept="image/jpeg,image/png,image/webp" onChange={handleFileSelect} className="hidden" />

            {!previewUrl ? (
                <button
                    type="button"
                    onClick={pickFile}
                    className="flex h-32 w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-input bg-muted/30 text-sm text-muted-foreground transition hover:bg-accent hover:text-accent-foreground"
                >
                    <Upload className="h-6 w-6" />
                    <span>Klik untuk pilih gambar</span>
                    <span className="text-xs">JPG/PNG/WEBP · max 5MB</span>
                </button>
            ) : (
                <div className="relative inline-block">
                    <img src={previewUrl} alt="Preview" className="h-32 w-32 rounded-lg border object-cover" />
                    <div className="absolute right-1 top-1 flex gap-1">
                        <Button type="button" size="icon" variant="secondary" className="h-7 w-7" onClick={pickFile} title="Ganti gambar">
                            <CropIcon className="h-3.5 w-3.5" />
                        </Button>
                        <Button type="button" size="icon" variant="destructive" className="h-7 w-7" onClick={removeImage} title="Hapus">
                            <X className="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>
            )}

            <Dialog open={cropOpen} onOpenChange={(v) => !uploading && setCropOpen(v)}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Crop Gambar</DialogTitle>
                        <DialogDescription>Geser & zoom untuk menentukan area, lalu klik Unggah.</DialogDescription>
                    </DialogHeader>

                    <div className="relative h-80 w-full overflow-hidden rounded-lg bg-muted">
                        {sourceImage && (
                            <Cropper
                                image={sourceImage}
                                crop={crop}
                                zoom={zoom}
                                rotation={rotation}
                                aspect={aspect}
                                onCropChange={setCrop}
                                onZoomChange={setZoom}
                                onRotationChange={setRotation}
                                onCropComplete={onCropComplete}
                            />
                        )}
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <span className="w-16 text-xs text-muted-foreground">Zoom</span>
                            <input type="range" min={1} max={3} step={0.05} value={zoom} onChange={(e) => setZoom(Number(e.target.value))} className="flex-1" />
                        </div>
                        <Button type="button" size="sm" variant="outline" onClick={() => setRotation((r) => (r + 90) % 360)}>
                            <RotateCw className="h-3.5 w-3.5" /> Putar 90°
                        </Button>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => setCropOpen(false)} disabled={uploading}>Batal</Button>
                        <Button type="button" onClick={uploadCropped} disabled={uploading}>
                            {uploading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
                            {uploading ? 'Mengunggah…' : 'Unggah'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}

/**
 * Helper: hasilkan Blob dari area crop + rotation.
 */
async function getCroppedBlob(imageSrc, areaPx, rotation = 0) {
    const image = await loadImage(imageSrc);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    const rad = (rotation * Math.PI) / 180;
    const safeArea = Math.max(image.width, image.height) * 2;
    canvas.width = safeArea;
    canvas.height = safeArea;
    ctx.translate(safeArea / 2, safeArea / 2);
    ctx.rotate(rad);
    ctx.translate(-image.width / 2, -image.height / 2);
    ctx.drawImage(image, 0, 0);

    const data = ctx.getImageData(0, 0, safeArea, safeArea);

    canvas.width = areaPx.width;
    canvas.height = areaPx.height;
    ctx.putImageData(
        data,
        Math.round(0 - safeArea / 2 + image.width / 2 - areaPx.x),
        Math.round(0 - safeArea / 2 + image.height / 2 - areaPx.y),
    );

    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', 0.9);
    });
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}
