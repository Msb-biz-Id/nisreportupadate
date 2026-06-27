<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            // Composite index for brand filtering with date ranges (dashboard queries)
            $table->index(['brand_id', 'created_at'], 'idx_brand_created');
            $table->index(['brand_id', 'status_po'], 'idx_brand_status');
            $table->index(['brand_id', 'is_lunas'], 'idx_brand_lunas');

            // Index for date-based queries
            $table->index('created_at', 'idx_orders_created');
            $table->index('published_at', 'idx_orders_published');
            $table->index('tanggal_masuk', 'idx_orders_tanggal_masuk');

            // Index for customer lookups
            $table->index('pelanggan_id', 'idx_orders_pelanggan');

            // Index for repeat order queries
            $table->index('repeat_from_po_id', 'idx_orders_repeat');

            // Index for category/source filtering
            $table->index('kategori_order_id', 'idx_orders_kategori');
            $table->index('sumber_order_id', 'idx_orders_sumber');

            // Index for special orders
            $table->index('is_special_order', 'idx_orders_special');
        });

        // Order items table indexes
        Schema::table('order_items', function (Blueprint $table) {
            // Composite index for order lookups with product filtering
            $table->index(['order_id', 'product_id'], 'idx_order_item_product');

            // Index for product analytics
            $table->index('product_id', 'idx_order_items_product');
            $table->index('nama_produk', 'idx_order_items_nama_produk');
        });

        // Order payments table indexes
        Schema::table('order_payments', function (Blueprint $table) {
            // Composite index for order payment history
            $table->index(['order_id', 'payment_date'], 'idx_order_payment_date');

            // Index for bank payment queries
            $table->index('bank_id', 'idx_order_payments_bank');

            // Index for verification status
            $table->index('verified_at', 'idx_order_payments_verified');

            // Index for payment type filtering
            $table->index('payment_type', 'idx_order_payments_type');

            // Index for master jenis pembayaran
            $table->index('master_jenis_pembayaran_id', 'idx_order_payments_jenis');
        });

        // Invoices table indexes
        Schema::table('invoices', function (Blueprint $table) {
            // Composite index for brand invoice queries
            $table->index(['brand_id', 'created_at'], 'idx_invoice_brand_created');
            $table->index(['brand_id', 'status'], 'idx_invoice_brand_status');

            // Index for order invoice relationship
            $table->index('order_id', 'idx_invoice_order');

            // Index for date-based queries
            $table->index('tanggal_terbit', 'idx_invoice_tanggal');
            $table->index('jatuh_tempo', 'idx_invoice_jatuh_tempo');

            // Index for status filtering
            $table->index('status', 'idx_invoice_status');
        });

        // Invoice items table indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            // Index for invoice lookups
            $table->index('invoice_id', 'idx_invoice_items_invoice');
        });

        // Refunds table indexes
        Schema::table('refunds', function (Blueprint $table) {
            // Composite index for brand refund queries
            $table->index(['brand_id', 'status'], 'idx_refund_brand_status');
            $table->index(['brand_id', 'created_at'], 'idx_refund_brand_created');

            // Index for order refund relationship
            $table->index('order_id', 'idx_refund_order');

            // Index for status filtering
            $table->index('status', 'idx_refund_status');

            // Index for review dates
            $table->index('reviewed_at', 'idx_refund_reviewed');
            $table->index('published_at', 'idx_refund_published');
        });

        // Design deposits table indexes
        Schema::table('design_deposits', function (Blueprint $table) {
            // Composite index for brand deposit queries
            $table->index(['brand_id', 'status'], 'idx_deposit_brand_status');
            $table->index(['brand_id', 'created_at'], 'idx_deposit_brand_created');

            // Index for customer lookups
            $table->index('customer_id', 'idx_deposit_customer');

            // Index for status filtering
            $table->index('status', 'idx_deposit_status');

            // Index for conversion tracking
            $table->index('converted_to_order_id', 'idx_deposit_converted');

            // Index for verification
            $table->index('verified_at', 'idx_deposit_verified');

            // Index for bank payment queries
            $table->index('bank_id', 'idx_deposit_bank');
        });

        // Brands table indexes
        Schema::table('brands', function (Blueprint $table) {
            // Index for parent-child brand hierarchy (hub-branch model)
            $table->index('parent_brand_id', 'idx_brands_parent');

            // Composite index for brand type hierarchy queries
            $table->index(['brand_type', 'parent_brand_id'], 'idx_brands_type_parent');

            // Index for brand type filtering
            $table->index('brand_type', 'idx_brands_type');

            // Index for active brands
            $table->index('is_active', 'idx_brands_active');
        });

        // Customers table indexes
        Schema::table('customers', function (Blueprint $table) {
            // Composite index for brand customer queries
            $table->index(['brand_id', 'created_at'], 'idx_customer_brand_created');

            // Index for customer type filtering
            $table->index('type_pelanggan_id', 'idx_customer_type');

            // Index for source filtering
            $table->index('sumber_daftar_id', 'idx_customer_source');

            // Index for geographic queries
            $table->index('provinsi_code', 'idx_customer_provinsi');
            $table->index('kabupaten_code', 'idx_customer_kabupaten');
            $table->index('kecamatan_code', 'idx_customer_kecamatan');
            $table->index('desa_code', 'idx_customer_desa');

            // Index for active customers
            $table->index('is_active', 'idx_customer_active');

            // Index for customer name search
            $table->index('nama', 'idx_customer_nama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_brand_created');
            $table->dropIndex('idx_brand_status');
            $table->dropIndex('idx_brand_lunas');
            $table->dropIndex('idx_orders_created');
            $table->dropIndex('idx_orders_published');
            $table->dropIndex('idx_orders_tanggal_masuk');
            $table->dropIndex('idx_orders_pelanggan');
            $table->dropIndex('idx_orders_repeat');
            $table->dropIndex('idx_orders_kategori');
            $table->dropIndex('idx_orders_sumber');
            $table->dropIndex('idx_orders_special');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_item_product');
            $table->dropIndex('idx_order_items_product');
            $table->dropIndex('idx_order_items_nama_produk');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropIndex('idx_order_payment_date');
            $table->dropIndex('idx_order_payments_bank');
            $table->dropIndex('idx_order_payments_verified');
            $table->dropIndex('idx_order_payments_type');
            $table->dropIndex('idx_order_payments_jenis');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_brand_created');
            $table->dropIndex('idx_invoice_brand_status');
            $table->dropIndex('idx_invoice_order');
            $table->dropIndex('idx_invoice_tanggal');
            $table->dropIndex('idx_invoice_jatuh_tempo');
            $table->dropIndex('idx_invoice_status');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_invoice');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex('idx_refund_brand_status');
            $table->dropIndex('idx_refund_brand_created');
            $table->dropIndex('idx_refund_order');
            $table->dropIndex('idx_refund_status');
            $table->dropIndex('idx_refund_reviewed');
            $table->dropIndex('idx_refund_published');
        });

        Schema::table('design_deposits', function (Blueprint $table) {
            $table->dropIndex('idx_deposit_brand_status');
            $table->dropIndex('idx_deposit_brand_created');
            $table->dropIndex('idx_deposit_customer');
            $table->dropIndex('idx_deposit_status');
            $table->dropIndex('idx_deposit_converted');
            $table->dropIndex('idx_deposit_verified');
            $table->dropIndex('idx_deposit_bank');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('idx_brands_parent');
            $table->dropIndex('idx_brands_type_parent');
            $table->dropIndex('idx_brands_type');
            $table->dropIndex('idx_brands_active');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customer_brand_created');
            $table->dropIndex('idx_customer_type');
            $table->dropIndex('idx_customer_source');
            $table->dropIndex('idx_customer_provinsi');
            $table->dropIndex('idx_customer_kabupaten');
            $table->dropIndex('idx_customer_kecamatan');
            $table->dropIndex('idx_customer_desa');
            $table->dropIndex('idx_customer_active');
            $table->dropIndex('idx_customer_nama');
        });
    }
};
