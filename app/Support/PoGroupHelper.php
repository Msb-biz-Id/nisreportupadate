<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PoGroupHelper
{
    public static function group(Collection $items): Collection
    {
        // 1. Separate addon items from non-addon items
        $nonAddon = $items->filter(fn($item) => is_array($item) ? !($item['is_addon'] ?? false) : !($item->is_addon ?? false));
        $addon = $items->filter(fn($item) => is_array($item) ? ($item['is_addon'] ?? false) : ($item->is_addon ?? false));

        // 2. Group non-addon items by specs hash
        $groups = $nonAddon->groupBy(function ($item) {
            return self::getSpecsHash($item);
        });

        // 3. Create GroupedOrderItem for each group
        $groupedNonAddon = $groups->map(function ($groupItems) {
            $representative = $groupItems->first();
            return new GroupedOrderItem($representative, $groupItems);
        })->values();

        // 4. Return combined collection (grouped non-addons + addons)
        return $groupedNonAddon->concat($addon);
    }

    public static function getSpecsHash($item): string
    {
        if (is_array($item)) {
            return md5(implode('|', [
                $item['jenis_setelan_id'] ?? '',
                $item['pola_produksi_id'] ?? '',
                $item['bahan_kain_id'] ?? '',
                json_encode($item['bahan_kain_ids'] ?? []),
                $item['bahan_kain_bawahan_id'] ?? '',
                json_encode($item['bahan_kain_bawahan_ids'] ?? []),
                strtolower(trim($item['warna'] ?? '')),
                $item['logo_id'] ?? '',
                json_encode($item['logo_ids'] ?? []),
                strtolower(trim($item['jenis_rib'] ?? '')),
                strtolower(trim($item['list_kerah'] ?? '')),
                strtolower(trim($item['list_lengan'] ?? '')),
                strtolower(trim($item['list_samping_celana'] ?? '')),
                strtolower(trim($item['list_bawah_celana'] ?? '')),
                strtolower(trim($item['tutup_kerah'] ?? '')),
                strtolower(trim($item['jenis_kerah'] ?? '')),
                $item['pola_jahitan_lengan_id'] ?? '',
                $item['pola_jahitan_kerah_id'] ?? '',
                $item['pola_jahitan_bawah_id'] ?? '',
                $item['pola_jahitan_pundak_id'] ?? '',
                $item['pola_jahitan_id'] ?? '',
                $item['gambar_desain'] ?? '',
                $item['gambar_kerah'] ?? '',
                $item['gambar_ket_tambahan'] ?? '',
                strtolower(trim($item['ket_atasan'] ?? '')),
                strtolower(trim($item['ket_bawahan'] ?? '')),
            ]));
        }

        return md5(implode('|', [
            $item->jenis_setelan_id,
            $item->pola_produksi_id,
            $item->bahan_kain_id,
            json_encode($item->bahan_kain_ids),
            $item->bahan_kain_bawahan_id,
            json_encode($item->bahan_kain_bawahan_ids),
            strtolower(trim($item->warna ?? '')),
            $item->logo_id,
            json_encode($item->logo_ids),
            strtolower(trim($item->jenis_rib ?? '')),
            strtolower(trim($item->list_kerah ?? '')),
            strtolower(trim($item->list_lengan ?? '')),
            strtolower(trim($item->list_samping_celana ?? '')),
            strtolower(trim($item->list_bawah_celana ?? '')),
            strtolower(trim($item->tutup_kerah ?? '')),
            strtolower(trim($item->jenis_kerah ?? '')),
            $item->pola_jahitan_lengan_id,
            $item->pola_jahitan_kerah_id,
            $item->pola_jahitan_bawah_id,
            $item->pola_jahitan_pundak_id,
            $item->pola_jahitan_id,
            $item->gambar_desain,
            $item->gambar_kerah,
            $item->gambar_ket_tambahan,
            strtolower(trim($item->ket_atasan ?? '')),
            strtolower(trim($item->ket_bawahan ?? '')),
        ]));
    }
}
