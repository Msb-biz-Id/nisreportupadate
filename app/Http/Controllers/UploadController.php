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

        // Authorization sederhana: butuh login. Validasi role detail di controller pemanggil.
        abort_unless($request->user(), 401);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $data['file'];
        $purpose = $data['purpose'];
        
        // Output format adalah selalu webp
        $filename = Str::ulid() . '.webp';

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

        // Tentukan full path tujuan di storage public
        $targetFullPath = Storage::disk('public')->path($path);

        // Lakukan kompresi ke WebP menggunakan GD Library
        $compressed = $this->compressToWebp($file->getRealPath(), $targetFullPath);

        if (!$compressed) {
            // Fallback: simpan apa adanya jika kompresi gagal
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
            $filename = Str::ulid() . '.' . $extension;
            $path = "{$folderPath}/{$filename}";
            
            $storedPath = $file->storeAs($folderPath, $filename, 'public');
            if (!$storedPath) {
                throw new \RuntimeException("Failed to store uploaded file on storage disk.");
            }
            $path = $storedPath;
        }

        // Validate physical file persistence on the disk
        if (!Storage::disk('public')->exists($path) || Storage::disk('public')->size($path) === 0) {
            throw new \RuntimeException("Uploaded file was not successfully persisted on storage disk.");
        }

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
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
            $maxWidth = 1200;

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

            // Simpan sebagai WebP berkualitas 75%
            $success = @imagewebp($image, $targetPath, 75);
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
        abort_unless($request->user(), 401);

        if (Storage::disk('public')->exists($data['path'])) {
            Storage::disk('public')->delete($data['path']);
        }
        return response()->json(['success' => true]);
    }
}
