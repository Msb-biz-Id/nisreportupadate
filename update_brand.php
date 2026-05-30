<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Brand;

$brand = Brand::where('kode', 'CRL')->first();
if ($brand) {
    $brand->update([
        'nama_brand' => 'Circle Sportwear',
        'tagline' => 'Premium Custom Sportswear & Uniforms',
        'alamat' => 'Jl. Merdeka No. 45, Bandung, Jawa Barat',
        'no_hp' => '081334455667',
        'email' => 'cs@circlesportwear.com',
        'website' => 'circlesportwear.com',
        'instagram' => '@circlesportwear',
        'facebook' => 'circlesportwear.id',
        'tiktok' => '@circlesportwear.official',
        'warna_primary' => '#1E3A8A', // Deep Blue
        'logo' => 'brand_logos/RJXS1uvCk33DrybK88h3bl7IJ4dNH5JJXUa0YoRn.png',
    ]);
    echo "Updated Brand Circle Sportwear successfully with logo!\n";
} else {
    echo "Brand CRL not found!\n";
}
