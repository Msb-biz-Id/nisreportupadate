<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\BrandContext;

class UploadController extends Controller
{
    public function image(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB
            'purpose' => ['required', 'string', 'in:products,orders,brands'],
            'nama_po' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        abort_unless($user, 401);

        $purpose = $data['purpose'];
        if ($purpose === 'brands') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['brand.create', 'brand.update', 'settings.brand'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk mengunggah gambar brand.');
            }
        } elseif ($purpose === 'products') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['master.produk', 'master.manage'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk mengunggah gambar produk.');
            }
        } elseif ($purpose === 'orders') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['order.create', 'order.update', 'production.update-progress', 'production.add-reject', 'finance.manage-invoice'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk mengunggah file order.');
            }
            $brand = BrandContext::currentBrand($request);
            if ($brand && !$user->hasAccessToBrand($brand->id)) {
                abort(403, 'Anda tidak memiliki akses ke brand aktif.');
            }
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];
        $purpose = $data['purpose'];
        
        // Dapatkan ekstensi aktual (jpg, png, webp) tanpa memaksa webp
        $clientExt = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        if ($clientExt === 'jpeg') {
            $clientExt = 'jpg';
        }
        if (!in_array($clientExt, ['jpg', 'png', 'webp'])) {
            $clientExt = 'jpg';
        }

        $filename = Str::ulid() . '.' . $clientExt;

        // Kelompokkan folder upload orders berdasarkan brand aktif jika ada
        $brand = BrandContext::currentBrand($request);
        $brandFolder = ($brand && $brand->id !== 'all') ? Str::slug($brand->nama_brand) : null;

        $subFolders = [];
        if ($purpose === 'orders' && $brandFolder) {
            $subFolders[] = $brandFolder;
        }
        if ($purpose === 'orders' && !empty($data['nama_po'])) {
            $subFolders[] = Str::slug($data['nama_po']);
        }

        $folderPath = $purpose;
        if (!empty($subFolders)) {
            $folderPath .= '/' . implode('/', $subFolders);
        }

        $path = "{$folderPath}/{$filename}";

        // Simpan file ke public storage disk
        $storedPath = $file->storeAs($folderPath, $filename, 'public');
        if (!$storedPath) {
            throw new \RuntimeException("Failed to store uploaded file on storage disk.");
        }
        $path = $storedPath;

        // Validate physical file persistence on the disk
        if (!Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) === 0) {
            throw new \RuntimeException("Uploaded file was not successfully persisted on storage disk.");
        }

        // Optimize high-resolution uploaded images to reduce storage size while preserving HD quality
        $fullRealPath = Storage::disk('public')->path($path);
        $this->optimizeUploadedImage($fullRealPath, $clientExt);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => $disk->url($path),
        ]);
    }

    /**
     * Kompres file gambar di disk tanpa mengorbankan kualitas visual (Lossless/High-Quality Compression).
     */
    private function optimizeUploadedImage(string $filePath, string $ext): void
    {
        try {
            if (!file_exists($filePath) || filesize($filePath) === 0) return;

            $info = @getimagesize($filePath);
            if (!$info) return;

            $width = $info[0];
            $height = $info[1];
            $mime = $info['mime'];

            // Hanya optimasi jika file berukuran besar (> 400KB atau lebar/tinggi > 2000px)
            if (filesize($filePath) < 400 * 1024 && $width <= 2000 && $height <= 2000) {
                return;
            }

            switch ($mime) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($filePath);
                    if ($image && function_exists('exif_read_data')) {
                        $exif = @exif_read_data($filePath);
                        $orientation = $exif['Orientation'] ?? 1;
                        switch ($orientation) {
                            case 3:
                                $rotated = @imagerotate($image, 180, 0);
                                if ($rotated) { imagedestroy($image); $image = $rotated; }
                                break;
                            case 6:
                                $rotated = @imagerotate($image, -90, 0);
                                if ($rotated) { imagedestroy($image); $image = $rotated; }
                                break;
                            case 8:
                                $rotated = @imagerotate($image, 90, 0);
                                if ($rotated) { imagedestroy($image); $image = $rotated; }
                                break;
                        }
                    }
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($filePath);
                    if ($image) {
                        imagealphablending($image, false);
                        imagesavealpha($image, true);
                    }
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($filePath);
                    break;
                default:
                    $image = null;
            }

            if (!$image) return;

            $maxWidth = 2000;
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) (($height / $width) * $maxWidth);
                $resized = imagescale($image, $newWidth, $newHeight);
                if ($resized) {
                    imagedestroy($image);
                    $image = $resized;
                }
            }

            // Simpan kembali dengan kualitas tinggi (90%) tanpa mengorbankan ketajaman visual
            if ($mime === 'image/jpeg') {
                @imagejpeg($image, $filePath, 90);
            } elseif ($mime === 'image/png') {
                @imagepng($image, $filePath, 8);
            } elseif ($mime === 'image/webp') {
                @imagewebp($image, $filePath, 90);
            }

            imagedestroy($image);
        } catch (\Throwable) {
            // Biarkan file asli jika terjadi kendala pada GD
        }
    }

    /**
     * Kompres dan resize gambar ke format WebP menggunakan PHP GD Library.
     */
    private function compressToWebp(string $tempPath, string $targetPath): bool
    {
        try {
            $info = @getimagesize($tempPath);
            if (!$info) return false;

            $mime = $info['mime'];
            
            switch ($mime) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($tempPath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($tempPath);
                    if ($image) {
                        imagealphablending($image, false);
                        imagesavealpha($image, true);
                    }
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($tempPath);
                    break;
                default:
                    return false;
            }

            if (!$image) return false;

            $width = imagesx($image);
            $height = imagesy($image);
            $maxWidth = 2400;

            // Resize secara proporsional jika lebar melebihi batas maksimum
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = (int) (($height / $width) * $maxWidth);
                
                $resizedImage = imagescale($image, $newWidth, $newHeight);
                if ($resizedImage) {
                    imagedestroy($image);
                    $image = $resizedImage;
                }
            }

            $dir = dirname($targetPath);
            if (!file_exists($dir)) {
                @mkdir($dir, 0755, true);
            }

            // Simpan sebagai WebP berkualitas 90% (Sangat Tajam & Optimal)
            $success = @imagewebp($image, $targetPath, 90);
            imagedestroy($image);

            return $success;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'regex:#^(products|orders|brands)/[A-Za-z0-9_/.-]+$#'],
        ]);

        if (str_contains($data['path'], '..')) {
            abort(400, 'Akses ditolak: nama path tidak valid.');
        }

        $user = $request->user();
        abort_unless($user, 401);

        $parts = explode('/', $data['path']);
        $purpose = $parts[0];

        if ($purpose === 'brands') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['brand.create', 'brand.update', 'settings.brand'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk menghapus gambar brand.');
            }
        } elseif ($purpose === 'products') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['master.produk', 'master.manage'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk menghapus gambar produk.');
            }
        } elseif ($purpose === 'orders') {
            if (!$user->isSuperadmin() && !$user->hasAnyPermission(['order.create', 'order.update', 'production.update-progress', 'production.add-reject', 'finance.manage-invoice'])) {
                abort(403, 'Anda tidak memiliki wewenang untuk menghapus file order.');
            }

            // Multi-tenant folder isolation check
            if (!$user->isSuperadmin() && !$user->hasRole(['owner', 'supervisor', 'admin_keuangan', 'admin_produksi'])) {
                $allowedBrandIds = BrandContext::effectiveBrandIds($request, 'all');
                $allowedBrandSlugs = \App\Models\Brand::whereIn('id', $allowedBrandIds)
                    ->pluck('nama_brand')
                    ->map(fn($name) => Str::slug($name))
                    ->toArray();

                $currentBrand = BrandContext::currentBrand($request);
                if ($currentBrand) {
                    $allowedBrandSlugs[] = Str::slug($currentBrand->nama_brand);
                }
                $allowedBrandSlugs = array_values(array_unique($allowedBrandSlugs));

                if (isset($parts[1]) && !in_array($parts[1], $allowedBrandSlugs)) {
                    abort(403, 'Anda tidak memiliki akses ke brand dari file ini.');
                }
            }
        } else {
            abort(403, 'Tindakan tidak diizinkan.');
        }

        // Periksa apakah file masih digunakan oleh entity/order lain (Reference-Safe Delete)
        if (\App\Support\FileReferenceChecker::isReferenced($data['path'])) {
            return response()->json([
                'success' => true,
                'deleted_physical' => false,
                'message' => 'Referensi dihapus, file fisik tetap disimpan karena digunakan oleh data lain.'
            ]);
        }

        if (Storage::disk('public')->exists($data['path'])) {
            Storage::disk('public')->delete($data['path']);
        }
        return response()->json(['success' => true, 'deleted_physical' => true]);
    }
}
