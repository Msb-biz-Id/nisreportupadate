<?php
 
namespace App\Services;
 
use App\Models\Order\Order;
use App\Models\Order\POVersion;
use App\Models\Order\POChangeLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
 
class POVersionManager
{
    /**
     * Build a complete JSON-serializable snapshot of the PO, its items, and its namesets.
     */
    public function buildSnapshot(Order $order): array
    {
        $order->load(['items.namesets']);
 
        return [
            'id' => $order->id,
            'no_po' => $order->no_po,
            'nama_po' => $order->nama_po,
            'status_po' => $order->status_po,
            'is_special_order' => (bool) $order->is_special_order,
            'tanggal_masuk' => $order->tanggal_masuk instanceof \Carbon\Carbon ? $order->tanggal_masuk->toDateString() : $order->tanggal_masuk,
            'deadline_customer' => $order->deadline_customer instanceof \Carbon\Carbon ? $order->deadline_customer->toDateString() : $order->deadline_customer,
            'kategori_order_id' => $order->kategori_order_id,
            'jenis_order_id' => $order->jenis_order_id,
            'sumber_order_id' => $order->sumber_order_id,
            'paket_order_id' => $order->paket_order_id,
            'jenis_setelan_id' => $order->jenis_setelan_id,
            'pola_produksi_id' => $order->pola_produksi_id,
            'pelanggan_id' => $order->pelanggan_id,
            'printing_ids' => $order->printing_ids,
            'iklan_id' => $order->iklan_id,
            'catatan' => $order->catatan,
            'total_tagihan' => (float) $order->total_tagihan,
            'reseller_display_brand_id' => $order->reseller_display_brand_id,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'jenis_produk_id' => $item->jenis_produk_id,
                    'is_addon' => (bool) $item->is_addon,
                    'nama_produk' => $item->nama_produk,
                    'varian_label' => $item->varian_label,
                    'quantity' => (int) $item->quantity,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'discount_type' => $item->discount_type,
                    'discount_value' => (float) $item->discount_value,
                    'discount_amount' => (float) $item->discount_amount,
                    'subtotal' => (float) $item->subtotal,
                    'bahan_kain_id' => $item->bahan_kain_id,
                    'bahan_kain_ids' => $item->bahan_kain_ids,
                    'bahan_kain_bawahan_id' => $item->bahan_kain_bawahan_id,
                    'bahan_kain_bawahan_ids' => $item->bahan_kain_bawahan_ids,
                    'jenis_setelan' => $item->jenis_setelan,
                    'jenis_setelan_id' => $item->jenis_setelan_id,
                    'pola' => $item->pola,
                    'pola_produksi_id' => $item->pola_produksi_id,
                    'logo_id' => $item->logo_id,
                    'logo_ids' => $item->logo_ids,
                    'printing_id' => $item->printing_id,
                    'resleting_id' => $item->resleting_id,
                    'jenis_rib' => $item->jenis_rib,
                    'tutup_kerah' => $item->tutup_kerah,
                    'list_kerah' => $item->list_kerah,
                    'list_lengan' => $item->list_lengan,
                    'list_samping_celana' => $item->list_samping_celana,
                    'list_bawah_celana' => $item->list_bawah_celana,
                    'pola_jahitan_lengan_id' => $item->pola_jahitan_lengan_id,
                    'pola_jahitan_kerah_id' => $item->pola_jahitan_kerah_id,
                    'pola_jahitan_bawah_id' => $item->pola_jahitan_bawah_id,
                    'pola_jahitan_pundak_id' => $item->pola_jahitan_pundak_id,
                    'pola_jahitan_id' => $item->pola_jahitan_id,
                    'pola_jahitan_config' => $item->pola_jahitan_config,
                    'jahitan_list_lengan' => $item->jahitan_list_lengan,
                    'warna' => $item->warna,
                    'jml_atasan' => $item->jml_atasan,
                    'jml_bawahan' => $item->jml_bawahan,
                    'gambar_desain' => $item->gambar_desain,
                    'ket_atasan' => $item->ket_atasan,
                    'ket_bawahan' => $item->ket_bawahan,
                    'gambar_kerah' => $item->gambar_kerah,
                    'gambar_ket_tambahan' => $item->gambar_ket_tambahan,
                    'jenis_kerah' => $item->jenis_kerah,
                    'catatan' => $item->catatan,
                    'namesets' => $item->namesets->map(function ($n) {
                        return [
                            'id' => $n->id,
                            'nama_punggung' => $n->nama_punggung,
                            'nomor_punggung' => $n->nomor_punggung,
                            'nama_dada' => $n->nama_dada,
                            'nomor_dada' => $n->nomor_dada,
                            'nama_lengan' => $n->nama_lengan,
                            'nomor_lengan' => $n->nomor_lengan,
                            'nomor_punggung_2' => $n->nomor_punggung_2,
                            'nama_punggung_2' => $n->nama_punggung_2,
                            'size_id' => $n->size_id,
                            'size_label' => $n->size_label,
                            'size_celana_id' => $n->size_celana_id,
                            'size_celana_label' => $n->size_celana_label,
                            'keterangan' => $n->keterangan,
                            'urutan' => $n->urutan,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }
 
    /**
     * Save a new version snapshot of the PO.
     */
    public function saveVersion(Order $order, User $user, ?string $changeReason = null): POVersion
    {
        $snapshot = $this->buildSnapshot($order);
 
        // Determine next version number
        $latestVersion = POVersion::where('order_id', $order->id)->max('version') ?? 0;
        $nextVersion = $latestVersion + 1;
 
        return POVersion::create([
            'order_id' => $order->id,
            'version' => $nextVersion,
            'metadata' => $snapshot,
            'created_by' => $user->id,
            'change_reason' => $changeReason ?? 'Pembaruan data PO',
        ]);
    }
 
    /**
     * Compare two snapshots and return a list of differences.
     */
    public function compareSnapshots(array $old, array $new): array
    {
        $diffs = [];
 
        // Fields on order level to compare
        $orderFields = [
            'nama_po' => 'Nama PO',
            'is_special_order' => 'Special Order',
            'tanggal_masuk' => 'Tanggal Masuk',
            'deadline_customer' => 'Deadline Customer',
            'catatan' => 'Catatan PO',
            'reseller_display_brand_id' => 'Reseller Display Brand',
            'total_tagihan' => 'Total Tagihan',
        ];
 
        foreach ($orderFields as $field => $label) {
            $oldVal = $old[$field] ?? null;
            $newVal = $new[$field] ?? null;
            if ($oldVal != $newVal) {
                $diffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }
 
        // Compare items
        $oldItems = collect($old['items'] ?? []);
        $newItems = collect($new['items'] ?? []);
 
        $oldItemsKeyed = $oldItems->keyBy('id');
        $newItemsKeyed = $newItems->keyBy('id');
 
        // 1. Removed items
        foreach ($oldItemsKeyed as $id => $oldItem) {
            if (!$newItemsKeyed->has($id)) {
                $diffs[] = [
                    'field' => 'item_removed',
                    'label' => 'Item Dihapus',
                    'old' => $oldItem['nama_produk'] . ($oldItem['varian_label'] ? " ({$oldItem['varian_label']})" : ''),
                    'new' => null,
                ];
            }
        }
 
        // 2. Added items
        foreach ($newItemsKeyed as $id => $newItem) {
            if (!$oldItemsKeyed->has($id)) {
                $diffs[] = [
                    'field' => 'item_added',
                    'label' => 'Item Ditambahkan',
                    'old' => null,
                    'new' => $newItem['nama_produk'] . ($newItem['varian_label'] ? " ({$newItem['varian_label']})" : '') . " [Qty: {$newItem['quantity']}, Harga: {$newItem['harga_satuan']}]",
                ];
            } else {
                // 3. Modified items
                $oldItem = $oldItemsKeyed->get($id);
                $itemFields = [
                    'nama_produk' => 'Nama Produk',
                    'varian_label' => 'Varian',
                    'quantity' => 'Quantity',
                    'harga_satuan' => 'Harga Satuan',
                    'discount_value' => 'Nilai Diskon',
                    'subtotal' => 'Subtotal Item',
                    'bahan_kain_id' => 'Bahan Kain',
                    'jenis_setelan' => 'Jenis Setelan',
                ];
                foreach ($itemFields as $field => $label) {
                    $oldVal = $oldItem[$field] ?? null;
                    $newVal = $newItem[$field] ?? null;
                    if ($oldVal != $newVal) {
                        $diffs[] = [
                            'field' => "item_{$field}",
                            'label' => "Item ({$newItem['nama_produk']}) - {$label}",
                            'old' => $oldVal,
                            'new' => $newVal,
                        ];
                    }
                }
 
                // Compare namesets for this item
                $oldNamesets = collect($oldItem['namesets'] ?? []);
                $newNamesets = collect($newItem['namesets'] ?? []);
 
                $oldNamesetsKeyed = $oldNamesets->keyBy('id');
                $newNamesetsKeyed = $newNamesets->keyBy('id');
 
                // Namesets removed
                foreach ($oldNamesetsKeyed as $nid => $oldN) {
                    if (!$newNamesetsKeyed->has($nid)) {
                        $diffs[] = [
                            'field' => 'nameset_removed',
                            'label' => "Nameset Dihapus dari {$newItem['nama_produk']}",
                            'old' => "{$oldN['nama_punggung']} (#{$oldN['nomor_punggung']}) - Ukuran: {$oldN['size_label']}",
                            'new' => null,
                        ];
                    }
                }
 
                // Namesets added
                foreach ($newNamesetsKeyed as $nid => $newN) {
                    if (!$oldNamesetsKeyed->has($nid)) {
                        $diffs[] = [
                            'field' => 'nameset_added',
                            'label' => "Nameset Ditambahkan ke {$newItem['nama_produk']}",
                            'old' => null,
                            'new' => "{$newN['nama_punggung']} (#{$newN['nomor_punggung']}) - Ukuran: {$newN['size_label']}",
                        ];
                    } else {
                        // Nameset modified
                        $oldN = $oldNamesetsKeyed->get($nid);
                        $nFields = [
                            'nama_punggung' => 'Nama Punggung',
                            'nomor_punggung' => 'Nomor Punggung',
                            'size_label' => 'Ukuran',
                            'keterangan' => 'Keterangan',
                        ];
                        foreach ($nFields as $field => $label) {
                            $oldVal = $oldN[$field] ?? null;
                            $newVal = $newN[$field] ?? null;
                            if ($oldVal != $newVal) {
                                $diffs[] = [
                                    'field' => "nameset_{$field}",
                                    'label' => "Nameset ({$newN['nama_punggung']}) - {$label}",
                                    'old' => $oldVal,
                                    'new' => $newVal,
                                ];
                            }
                        }
                    }
                }
            }
        }
 
        return $diffs;
    }
 
    /**
     * Unify/Consolidate change logging based on the difference between old and new state.
     */
    public function logChanges(Order $order, User $user, array $oldSnapshot, array $newSnapshot, string $changeReason): void
    {
        $diffs = $this->compareSnapshots($oldSnapshot, $newSnapshot);
 
        foreach ($diffs as $diff) {
            POChangeLog::create([
                'order_id' => $order->id,
                'changed_by' => $user->id,
                'change_reason' => $changeReason,
                'field_changed' => $diff['label'],
                'old_value' => is_scalar($diff['old']) || $diff['old'] === null ? (string) $diff['old'] : json_encode($diff['old']),
                'new_value' => is_scalar($diff['new']) || $diff['new'] === null ? (string) $diff['new'] : json_encode($diff['new']),
            ]);
        }
    }
}
