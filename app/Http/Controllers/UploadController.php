<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Endpoint upload gambar generic. Path disimpan per "purpose" (products, orders, brands).
     * Return URL public yang bisa disimpan di field model.
     */
    public function image(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB
            'purpose' => ['required', 'string', 'in:products,orders,brands'],
        ]);

        // Authorization sederhana: butuh login. Validasi role detail di controller pemanggil.
        abort_unless($request->user(), 401);

        $file = $data['file'];
        $purpose = $data['purpose'];
        $filename = Str::ulid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($purpose, $filename, 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'regex:#^(products|orders|brands)/[A-Za-z0-9_.-]+$#'],
        ]);
        abort_unless($request->user(), 401);

        if (Storage::disk('public')->exists($data['path'])) {
            Storage::disk('public')->delete($data['path']);
        }
        return response()->json(['success' => true]);
    }
}
