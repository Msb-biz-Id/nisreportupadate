<?php

namespace Tests\Feature\Console;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DbRefreshSafeTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        $backupPath = database_path('backup_orders_test.json');
        if (file_exists($backupPath)) {
            @unlink($backupPath);
        }
        parent::tearDown();
    }

    public function test_db_refresh_safe_command_backups_and_restores_data(): void
    {
        // 1. Run migrations & seeders to have basic setup
        $this->seed();

        // Ensure we have at least one brand
        $brand = Brand::first();
        $this->assertNotNull($brand);

        // Clear existing orders/customers to start clean
        Schema::disableForeignKeyConstraints();
        Order::truncate();
        Customer::truncate();
        Schema::enableForeignKeyConstraints();

        // Create a custom test customer and order
        $customer = Customer::create([
            'brand_id' => $brand->id,
            'kode' => 'ALG-CUST-999',
            'nama' => 'Test Customer Backup',
            'nomor_hp' => '08999999999',
            'email' => 'testbackup@example.com',
            'is_active' => true,
        ]);

        $user = User::first();
        
        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-TEST-BACKUP',
            'nama_po' => 'Test PO Backup',
            'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        // Assert they exist in the DB
        $this->assertDatabaseHas('customers', ['nama' => 'Test Customer Backup']);
        $this->assertDatabaseHas('orders', ['no_po' => 'PO-TEST-BACKUP']);

        // 2. Run the db:refresh-safe command
        $this->artisan('db:refresh-safe')
            ->expectsOutput('=== MEMULAI REFRESH DATABASE SECARA AMAN ===')
            ->expectsOutput('Membaca data dari database...')
            ->expectsOutput('Menjalankan perintah migrate:fresh --seed...')
            ->assertExitCode(0);

        // 3. Verify that the custom customer and order are restored/preserved!
        $this->assertDatabaseHas('customers', ['nama' => 'Test Customer Backup']);
        $this->assertDatabaseHas('orders', ['no_po' => 'PO-TEST-BACKUP']);
    }
}
