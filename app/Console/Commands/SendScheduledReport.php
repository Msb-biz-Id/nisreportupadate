<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Settings\SystemSetting;
use App\Services\Ai\GeminiClient;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Reports\ReportMessageBuilder;
use Illuminate\Console\Command;

class SendScheduledReport extends Command
{
    protected $signature = 'reports:send
        {periode=harian : harian|mingguan|bulanan}
        {--brand= : UUID brand spesifik}
        {--force : Kirim meski enable_auto_report = false}';

    protected $description = 'Kirim laporan otomatis per-role ke WhatsApp/Telegram (BRD 17.2.2)';

    public function handle(NotificationDispatcher $dispatcher): int
    {
        $periode = $this->argument('periode');
        $force   = $this->option('force');

        // Guard: cek apakah laporan otomatis diaktifkan
        $enabled = (bool) SystemSetting::get('reports', 'enable_auto_report', false);
        if (! $enabled && ! $force) {
            $this->warn('Laporan otomatis tidak aktif. Gunakan --force untuk memaksa pengiriman.');
            return self::SUCCESS;
        }

        // Builder dengan GeminiClient — AI insight opsional (skip jika key belum dikonfigurasi)
        $ai      = GeminiClient::fromSettings();
        $builder = new ReportMessageBuilder($ai);
        if ($ai->isConfigured()) {
            $this->info('Gemini terkonfigurasi — AI Insight akan disertakan dalam laporan.');
        }

        // Jenis laporan yang diaktifkan (default: semua jenis)
        $typesRaw = SystemSetting::get('reports', 'report_types', 'superadmin,brand,produksi,owner,keuangan');
        $types    = array_filter(array_map('trim', explode(',', $typesRaw)));

        // Semua brand aktif diproses — hub, branch, dan regular diperlakukan sama.
        $brandId = $this->option('brand');
        $brands  = $brandId
            ? Brand::where('id', $brandId)->get()
            : Brand::where('is_active', true)->get();

        // Track total sent messages
        $totalSent = 0;

        // ── Superadmin report (satu pesan global, bukan per-brand)
        if (in_array('superadmin', $types)) {
            $recipients = $this->parseRecipients('superadmin_recipients', 'superadmin');
            if (! empty($recipients['whatsapp']) || ! empty($recipients['telegram']) || ! empty($recipients['email'])) {
                $message = $builder->superadmin($periode);
                $subject = "Laporan {$periode} Superadmin";
                $results = $dispatcher->send($message, $recipients, $subject);
                $sent    = collect($results)->where('success', true)->count();
                $totalSent += $sent;
                $this->info("[SUPERADMIN] Terkirim: {$sent}/" . count($results));
            } else {
                $this->warn('[SUPERADMIN] Tidak ada recipients terkonfigurasi.');
            }
        }

        // ── Per-brand reports
        foreach ($brands as $brand) {
            if (! $force && ! $this->brandHasActivityOrOrders($brand, $periode)) {
                $this->info("── Brand: {$brand->kode} (Dilewati - Tidak ada order aktif atau aktivitas) ──");
                continue;
            }

            $this->info("── Brand: {$brand->kode} ──");

            if (in_array('produksi', $types)) {
                $r = $this->parseRecipients('produksi_recipients', 'admin_produksi', $brand->id);
                if (! empty($r['whatsapp']) || ! empty($r['telegram']) || ! empty($r['email'])) {
                    $msg     = $builder->adminProduksi($brand, $periode);
                    $subject = "Laporan Produksi {$periode} — {$brand->kode}";
                    $results = $dispatcher->send($msg, $r, $subject);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [PRODUKSI] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('brand', $types)) {
                $r = $this->parseRecipients('brand_recipients', 'admin_brand', $brand->id);
                if (! empty($r['whatsapp']) || ! empty($r['telegram']) || ! empty($r['email'])) {
                    $msg     = $builder->adminBrand($brand, $periode);
                    $subject = "Laporan Brand {$periode} — {$brand->kode}";
                    $results = $dispatcher->send($msg, $r, $subject);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [BRAND] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('owner', $types)) {
                $r = $this->parseRecipients('owner_recipients', 'owner', $brand->id);
                if (! empty($r['whatsapp']) || ! empty($r['telegram']) || ! empty($r['email'])) {
                    $msg     = $builder->owner($brand, $periode);
                    $subject = "Laporan Ringkasan Executive {$periode} — {$brand->kode}";
                    $results = $dispatcher->send($msg, $r, $subject);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [OWNER] {$brand->kode}: {$sent}/" . count($results));
                }
            }

            if (in_array('keuangan', $types)) {
                $r = $this->parseRecipients('keuangan_recipients', 'admin_keuangan', $brand->id);
                if (! empty($r['whatsapp']) || ! empty($r['telegram']) || ! empty($r['email'])) {
                    $msg     = $builder->keuangan($brand, $periode);
                    $subject = "Laporan Keuangan {$periode} — {$brand->kode}";
                    $results = $dispatcher->send($msg, $r, $subject);
                    $sent    = collect($results)->where('success', true)->count();
                    $totalSent += $sent;
                    $this->info("  [KEUANGAN] {$brand->kode}: {$sent}/" . count($results));
                }
            }
        }

        $this->info("Selesai. Total terkirim: {$totalSent}");
        return self::SUCCESS;
    }

    /**
     * Parse recipients dari settings ke format dispatcher.
     * Fallback ke database User dengan matching Role & Brand Access secara presisi.
     */
    private function parseRecipients(string $settingKey, ?string $roleName = null, ?string $brandId = null): array
    {
        $raw = SystemSetting::get('reports', $settingKey, '');
        $items = array_filter(array_map('trim', explode(',', $raw ?? '')));

        $wa = [];
        $tg = [];
        $email = [];

        foreach ($items as $item) {
            if (filter_var($item, FILTER_VALIDATE_EMAIL)) {
                $email[] = $item;
            } elseif (str_starts_with($item, '-') || (!str_starts_with($item, '62') && !str_starts_with($item, '08') && is_numeric($item) && strlen($item) < 11)) {
                $tg[] = $item;
            } else {
                $wa[] = $item;
            }
        }

        // Jika kolom pengaturan kosong/tidak diisi manual override, cari dinamis berdasarkan User & Role & Brand Access
        if (empty($wa) && empty($tg) && empty($email) && $roleName) {
            // Tentukan list role yang relevan
            $matchingRoles = match ($roleName) {
                'admin_brand'    => ['admin_brand', 'admin_reseller'],
                'admin_produksi' => ['admin_produksi', 'supervisor'],
                'owner'          => ['owner'],
                default          => [$roleName],
            };

            $usersQuery = \App\Models\User::where('is_active', true)
                ->whereHas('roles', function ($q) use ($matchingRoles) {
                    $q->whereIn('name', $matchingRoles);
                });

            $users = $usersQuery->get();

            // Filter brand access jika laporan ini bersifat per-brand
            if ($brandId) {
                $users = $users->filter(function ($u) use ($brandId) {
                    // Jika user memiliki brand assignment eksplisit di pivot user_brand_access,
                    // utamakan pivot tersebut agar tidak tertukar/salah kamar antar brand!
                    if ($u->brands()->exists()) {
                        return $u->brands()->where('brands.id', $brandId)->exists();
                    }
                    return $u->hasAccessToBrand($brandId);
                });
            }

            foreach ($users as $u) {
                if ($u->phone) {
                    $wa[] = trim($u->phone);
                }
                if ($u->telegram_chat_id) {
                    $tg[] = trim($u->telegram_chat_id);
                }
                if ($u->email) {
                    $email[] = trim($u->email);
                }
            }
        }

        $wa = array_values(array_unique($wa));
        $tg = array_values(array_unique($tg));
        $email = array_values(array_unique($email));

        // Global fallback HANYA jika TIDAK ADA SAMA SEKALI recipient yang teridentifikasi dari channel apapun
        if (empty($wa) && empty($tg) && empty($email)) {
            $defaultWa = SystemSetting::get('whatsapp', 'default_recipient');
            if ($defaultWa) $wa = [$defaultWa];

            $defaultTg = SystemSetting::get('telegram', 'default_chat_id');
            if ($defaultTg) $tg = [$defaultTg];

            $defaultMail = SystemSetting::get('mail', 'mail_from_address', config('mail.from.address'));
            if ($defaultMail) $email = [$defaultMail];
        }

        return ['whatsapp' => $wa, 'telegram' => $tg, 'email' => $email];
    }

    private function brandHasActivityOrOrders(Brand $brand, string $periode): bool
    {
        // 1. Cek apakah ada PO yang aktif (bukan draft, selesai, atau sudah dikirim)
        $hasActive = \App\Models\Order\Order::where('brand_id', $brand->id)
            ->whereNotIn('status_po', ['draft', 'selesai', 'sudah_dikirim'])
            ->exists();
        if ($hasActive) {
            return true;
        }

        // Tentukan rentang waktu berdasarkan periode laporan
        $range = match ($periode) {
            'mingguan' => [now()->startOfWeek()->startOfDay(), now()->endOfWeek()->endOfDay()],
            'bulanan'  => [now()->startOfMonth()->startOfDay(), now()->endOfMonth()->endOfDay()],
            default    => [today()->startOfDay(), today()->endOfDay()], // harian
        };

        // 2. Cek apakah ada PO baru atau PO yang diupdate dalam periode terkait
        $hasOrderActivity = \App\Models\Order\Order::where('brand_id', $brand->id)
            ->whereBetween('updated_at', $range)
            ->exists();
        if ($hasOrderActivity) {
            return true;
        }

        // 3. Cek apakah ada pembayaran (DP/pelunasan/cashback) terverifikasi dalam periode terkait
        $hasPaymentActivity = \App\Models\Order\OrderPayment::whereHas('order', function ($q) use ($brand) {
                $q->where('brand_id', $brand->id);
            })
            ->whereNotNull('verified_at')
            ->whereBetween('payment_date', $range)
            ->exists();
        if ($hasPaymentActivity) {
            return true;
        }

        // 4. Cek apakah ada refund yang di-publish dalam periode terkait
        $hasRefundActivity = \App\Models\Order\Refund::where('brand_id', $brand->id)
            ->where('status', 'published')
            ->whereBetween('published_at', $range)
            ->exists();
        if ($hasRefundActivity) {
            return true;
        }

        // 5. Cek apakah ada insiden produk rijek dalam periode terkait
        $hasRijekActivity = \App\Models\Order\Rijek::whereHas('order', function ($q) use ($brand) {
                $q->where('brand_id', $brand->id);
            })
            ->whereBetween('created_at', $range)
            ->exists();
        if ($hasRijekActivity) {
            return true;
        }

        return false;
    }
}
