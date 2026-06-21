# Desain Arsitektur Baru: Sistem Notifikasi Sistem yang Ideal (Modern, Asinkron, & Bebas Duplikasi)

Dokumen ini menjelaskan rancangan sistem notifikasi baru yang didesain secara modular untuk mengatasi kelemahan sistem lama (seperti penumpukan notifikasi/double alerts, performa lambat karena synchronous dispatch, database locking akibat polling agresif, dan redundansi suara di multi-tab).

---

## 1. Perbandingan Masalah & Solusi Ideal

| Komponen | Sistem Lama (Masalah) | Sistem Baru (Solusi Ideal) |
| :--- | :--- | :--- |
| **Eksekusi Dispatch** | **Synchronous (Lambat)**: Notifikasi dikirim langsung saat request HTTP berjalan. Jika gateway WA/Telegram lambat, user harus menunggu loading. | **Asynchronous (Cepat)**: Menggunakan Laravel Queue (`database`/`redis` driver). Transaksi selesai instan, pengiriman WA/Telegram berjalan di background worker. |
| **Deduplikasi Alert** | **Tidak Ada**: Aksi beruntun (misalnya validasi masal) memicu puluhan notifikasi terpisah yang menumpuk di layar. | **Deduplication Engine**: Memeriksa sidik jari (*hash key*) dari `event_type + entity_id + user_id` dalam rentang waktu singkat (misal: 1 menit) untuk mengabaikan alert ganda. |
| **Penerimaan Frontend** | **Polling Agresif (3s Axios)** + **Laravel Echo**: Tab terus-menerus menembak server, menyebabkan penumpukan jika data terlambat sinkron. | **Event-Driven Only**: Menggunakan Laravel Echo (WebSockets/Reverb) secara penuh. Polling ditiadakan, atau hanya berjalan *sekali* sebagai fallback jika koneksi WebSocket terputus. |
| **Multi-tab (Multi-window)** | **Suara Ganda**: Jika user membuka 5 tab browser, kelima tab akan memutar suara bell secara bersamaan dan berisik. | **Tab Synchronization**: Menggunakan JavaScript **`BroadcastChannel API`** untuk berkomunikasi antar tab. Hanya tab utama yang memutar suara dan memunculkan desktop alert. |
| **Penyusunan Notifikasi** | **Kustom & Kaku**: Menggunakan `DynamicNotificationService` kustom yang sulit di-maintain. | **Laravel Native Notification**: Memanfaatkan class bawaan `Illuminate\Notifications\Notification` dengan multi-channel driver. |

---

## 2. Arsitektur Backend (Laravel)

### A. Menggunakan Laravel Native Notification
Setiap notifikasi didefinisikan sebagai class terpisah yang mengimplementasikan `ShouldQueue` agar otomatis dijalankan secara asinkron.

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class PaymentVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload) {}

    // Tentukan channel pengiriman dinamis berdasarkan konfigurasi role/user
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Pembayaran PO Diverifikasi',
            'body' => "Pembayaran untuk PO {$this->payload['no_po']} senilai {$this->payload['nominal']} telah diverifikasi.",
            'action_url' => $this->payload['action_url'],
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => 'Pembayaran PO Diverifikasi',
            'body' => "Pembayaran untuk PO {$this->payload['no_po']} senilai {$this->payload['nominal']} telah diverifikasi.",
            'action_url' => $this->payload['action_url'],
            'sound' => 'success-tada'
        ]);
    }
}
```

### B. Mekanisme Deduplikasi (Idempotency Helper)
Sebelum notifikasi dikirimkan ke antrean queue, kita melakukan pengecekan kunci unik (idempotency key) di Cache:

```php
namespace App\Support\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class IdealNotificationService
{
    public static function dispatch(string $eventKey, array $payload, array $targetRoles)
    {
        // 1. Buat sidik jari unik untuk mencegah duplikasi masal dalam 30 detik
        $fingerprint = md5($eventKey . '_' . ($payload['no_po'] ?? '') . '_' . json_encode($targetRoles));
        
        if (Cache::has('notif_lock_' . $fingerprint)) {
            return; // Abaikan jika event yang sama sudah dikirim barusan
        }
        Cache::put('notif_lock_' . $fingerprint, true, 30); // Lock selama 30 detik

        // 2. Ambil user target berdasarkan role
        $users = \App\Models\User::role($targetRoles)->get();

        // 3. Kirim notifikasi secara massal menggunakan Laravel Notification
        Notification::send($users, new \App\Notifications\SystemEventNotification($eventKey, $payload));
    }
}
```

---

## 3. Arsitektur Frontend (React + Laravel Echo)

### A. BroadcastChannel Sync untuk Menghindari Suara Ganda
Kita membuat sinkronisasi antar tab browser di React menggunakan `BroadcastChannel`. Ketika satu tab menerima notifikasi melalui WebSocket, ia memberi tahu tab lain agar tidak memutar suara/toast yang sama.

```javascript
// resources/js/Services/notificationSync.js
const notifChannel = new BroadcastChannel('nisreport_notifications');

export function subscribeToSystemNotifications(userId, onNewNotification) {
    if (!window.Echo) return;

    const privateChannel = window.Echo.private(`App.Models.User.${userId}`)
        .notification((notification) => {
            // Cek apakah ada tab lain yang sudah menandai notifikasi ini
            const isTabLeader = checkIsTabLeader();
            
            if (isTabLeader) {
                // Tab ini adalah leader: putar suara dan tampilkan toast
                onNewNotification(notification);
                
                // Siarkan ke tab lain bahwa notifikasi ini sudah di-handle
                notifChannel.postMessage({
                    type: 'NOTIF_RECEIVED',
                    id: notification.id
                });
            }
        });

    return () => {
        if (window.Echo) {
            window.Echo.leave(`App.Models.User.${userId}`);
        }
    };
}

// Logika sederhana untuk menentukan tab leader (bisa menggunakan localStorage)
function checkIsTabLeader() {
    const now = Date.now();
    localStorage.setItem('last_active_tab_ping', now);
    const tabId = sessionStorage.getItem('tab_id') || Math.random().toString();
    sessionStorage.setItem('tab_id', tabId);
    
    const currentLeader = localStorage.getItem('tab_leader');
    if (!currentLeader || now - parseInt(localStorage.getItem('tab_leader_ts') || '0') > 5000) {
        localStorage.setItem('tab_leader', tabId);
        localStorage.setItem('tab_leader_ts', now);
        return true;
    }
    return currentLeader === tabId;
}
```

### B. Global State & Toast Limiter
Pada `AppLayout.jsx`, kita membatasi antrean Sonner toast dengan menetapkan batas maksimal toast bertumpuk (misal: maksimal 3 toast sekaligus di layar) dan menggunakan model *grouped toast* jika ada notifikasi bertubi-tubi.

```javascript
// Mengatur batas sonner toast pada Layout Utama
<Toaster maxToasts={3} closeButton richColors position="top-right" />
```

---

## 4. Keuntungan Desain Baru
1. **Performa Server Ringan**: Server tidak lagi dibebani request Axios polling setiap 3 detik dari ribuan browser tab.
2. **Database Stabil**: Mengurangi write ops pada database karena adanya mekanisme cache locking (deduplikasi).
3. **UX Sangat Premium**: Tidak ada penumpukan bell/suara yang bising jika user membuka banyak halaman dalam tab baru.
4. **Reliabilitas Tinggi**: Gagal kirim WA/Telegram tidak membuat aplikasi web lag karena semua diproses asinkron di antrean Queue.
