<?php

namespace Tests\Feature\Public;

use App\Models\Master\Customer;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tracking_shows_published_po(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'Ahmad Surya', 'nomor_hp' => '081234567890', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-PUBLIC-001',
            'nama_po' => 'Public PO', 'status_po' => 'on_progress',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $response = $this->get('/track/PO-PUBLIC-001');
        $response->assertOk();
        // Page response berisi data tracking
        $response->assertSee('Public PO');
    }

    public function test_public_tracking_with_invalid_po_returns_not_found_view(): void
    {
        // Tetap return 200 — view menampilkan "PO Tidak Ditemukan"
        $this->get('/track/PO-INVALID')->assertOk();
    }

    public function test_draft_po_not_accessible_via_public_tracking(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'Secret', 'nomor_hp' => '081', 'is_active' => true]);

        Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-DRAFT-X',
            'nama_po' => 'Should not show', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        $response = $this->get('/track/PO-DRAFT-X');
        $response->assertOk();
        $response->assertDontSee('Should not show');
    }

    public function test_public_invoice_requires_published_status(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'X', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-INV-1',
            'nama_po' => 'P', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 500000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        // Draft invoice — public should NOT access
        $draftInv = Invoice::create([
            'brand_id' => $brand->id, 'order_id' => $order->id,
            'invoice_number' => 'INV-PUB-DRAFT',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft',
            'total_tagihan' => 500000,
            'sisa_pembayaran' => 500000,
            'created_by' => $user->id,
        ]);

        $this->get('/invoice/INV-PUB-DRAFT')->assertNotFound();

        // Published invoice — accessible
        $draftInv->update(['status' => 'published']);
        $this->get('/invoice/INV-PUB-DRAFT')->assertOk();
    }

    public function test_authorized_user_can_view_draft_invoice_on_tracking_page(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_produksi', [$brand]); // admin_produksi has order.view
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'X', 'nomor_hp' => '081', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-INV-TRACK-TEST',
            'nama_po' => 'P', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 500000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        $draftInv = Invoice::create([
            'brand_id' => $brand->id, 'order_id' => $order->id,
            'invoice_number' => 'INV-PUB-DRAFT-TRACK',
            'tanggal_terbit' => now()->toDateString(),
            'status' => 'draft',
            'total_tagihan' => 500000,
            'sisa_pembayaran' => 500000,
            'created_by' => $user->id,
        ]);

        // Unauthenticated guest should NOT see the draft invoice on tracking page
        $response = $this->get('/track/PO-INV-TRACK-TEST');
        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Public/Track')
            ->where('invoices', [])
        );

        // Authenticated admin_produksi user SHOULD see it on tracking page
        $response = $this->actingAs($user)->get('/track/PO-INV-TRACK-TEST');
        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Public/Track')
            ->has('invoices', 1)
            ->where('invoices.0.invoice_number', 'INV-PUB-DRAFT-TRACK')
        );
    }

    public function test_public_fo_preview_and_pdf(): void
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('admin_brand', [$brand]);
        Customer::create(['brand_id' => $brand->id, 'kode' => 'C', 'nama' => 'John Doe', 'nomor_hp' => '08122334455', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id, 'no_po' => 'PO-FO-PUB-123',
            'nama_po' => 'Format Order Public PO', 'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        // Unauthenticated guest can access public FO preview
        $response = $this->get('/fo/PO-FO-PUB-123');
        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Order/FoPreview')
            ->where('order.no_po', 'PO-FO-PUB-123')
            ->where('isPublic', true)
        );

        // Unauthenticated guest can access public FO pdf download
        $pdfResponse = $this->get('/fo/PO-FO-PUB-123/pdf');
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-disposition', 'attachment; filename=FO-PO-FO-PUB-123.pdf');
    }
}
