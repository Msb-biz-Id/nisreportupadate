<?php

namespace App\Http\Controllers;

use App\Support\BrandContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BrandSwitchController extends Controller
{
    public function __invoke(Request $request, string $brandId): RedirectResponse
    {
        $ok = BrandContext::set($request, $request->user(), $brandId);

        if (! $ok) {
            return back()->with('error', 'Anda tidak memiliki akses ke brand tersebut.');
        }

        return back()->with('success', 'Brand aktif diperbarui.');
    }
}
