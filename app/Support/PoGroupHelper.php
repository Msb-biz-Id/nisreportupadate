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

        // Apply free item specification inheritance from core items
        $nonAddon = self::applyFreeInheritance($nonAddon);

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

    public static function applyFreeInheritance(Collection $nonAddon): Collection
    {
        // Separate core (non-free) items
        $coreItems = $nonAddon->filter(function ($item) {
            $harga = is_array($item) ? ($item['harga_satuan'] ?? 0.0) : ($item->harga_satuan ?? 0.0);
            return (float)$harga > 0.0;
        });

        if ($coreItems->isEmpty()) {
            return $nonAddon;
        }

        $fields = [
            'jenis_setelan_id' => 'jenisSetelan',
            'pola_produksi_id' => 'polaProduksi',
            'bahan_kain_id' => 'bahanKain',
            'bahan_kain_ids' => null,
            'bahan_kains_names' => null,
            'bahan_kain_bawahan_id' => 'bahanKainBawahan',
            'bahan_kain_bawahan_ids' => null,
            'bahan_kain_bawahan_names' => null,
            'warna' => null,
            'logo_id' => 'logo',
            'logo_ids' => null,
            'logo_names' => null,
            'jenis_rib' => null,
            'list_kerah' => null,
            'list_lengan' => null,
            'list_samping_celana' => null,
            'list_bawah_celana' => null,
            'tutup_kerah' => null,
            'jenis_kerah' => null,
            'pola_jahitan_id' => 'polaJahitan',
            'pola_jahitan_lengan_id' => 'polaJahitanLengan',
            'pola_jahitan_kerah_id' => null,
            'pola_jahitan_bawah_id' => null,
            'pola_jahitan_pundak_id' => null,
            'resleting_id' => 'resleting',
            'jahitan_list_lengan' => null,
            'gambar_desain' => null,
            'gambar_kerah' => null,
            'gambar_ket_tambahan' => null,
            'ket_atasan' => null,
            'ket_bawahan' => null,
        ];

        return $nonAddon->map(function ($item) use ($coreItems, $fields) {
            $harga = is_array($item) ? ($item['harga_satuan'] ?? 0.0) : ($item->harga_satuan ?? 0.0);
            if ((float)$harga != 0.0) {
                return $item;
            }

            // Find matching core item
            $freeName = strtolower(trim(is_array($item) ? ($item['nama_produk'] ?? '') : ($item->nama_produk ?? '')));
            $freeVarian = strtolower(trim(is_array($item) ? ($item['varian_label'] ?? '') : ($item->varian_label ?? '')));

            $coreItem = null;
            if ($freeName !== '') {
                $coreItem = $coreItems->first(function ($c) use ($freeName) {
                    $cName = strtolower(trim(is_array($c) ? ($c['nama_produk'] ?? '') : ($c->nama_produk ?? '')));
                    return $cName === $freeName;
                });
            }

            if (!$coreItem && $freeVarian !== '') {
                $coreItem = $coreItems->first(function ($c) use ($freeVarian) {
                    $cVarian = strtolower(trim(is_array($c) ? ($c['varian_label'] ?? '') : ($c->varian_label ?? '')));
                    return $cVarian === $freeVarian;
                });
            }

            if (!$coreItem) {
                $coreItem = $coreItems->first();
            }

            if ($coreItem) {
                if (is_array($item)) {
                    foreach ($fields as $field => $relation) {
                        $itemVal = $item[$field] ?? null;
                        if (self::isFieldEmpty($itemVal)) {
                            $coreVal = $coreItem[$field] ?? null;
                            if (!self::isFieldEmpty($coreVal)) {
                                $item[$field] = $coreVal;
                            }
                        }
                    }
                } else {
                    foreach ($fields as $field => $relation) {
                        $itemVal = $item->getAttribute($field);
                        if (self::isFieldEmpty($itemVal)) {
                            $coreVal = $coreItem->getAttribute($field);
                            if (!self::isFieldEmpty($coreVal)) {
                                $item->setAttribute($field, $coreVal);
                                
                                // Set relation if loaded
                                if ($relation && $coreItem->relationLoaded($relation)) {
                                    $item->setRelation($relation, $coreItem->getRelation($relation));
                                }
                            }
                        }
                    }
                }
            }

            return $item;
        });
    }

    /**
     * Check if a field value is considered empty.
     *
     * @param mixed $val
     * @return bool
     */
    private static function isFieldEmpty(mixed $val): bool
    {
        if (is_null($val)) return true;
        if (is_array($val)) return empty($val);
        if (is_string($val)) return trim($val) === '';
        return false;
    }

    /**
     * Generate specs hash for item.
     *
     * @param mixed $item
     * @return string
     */
    public static function getSpecsHash(mixed $item): string
    {
        if (is_array($item)) {
            $isFree = ((float)($item['harga_satuan'] ?? 0.0) === 0.0) ? 'free' : 'paid';
            return md5(implode('|', [
                $isFree,
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

        $isFree = ((float)($item->harga_satuan ?? 0.0) === 0.0) ? 'free' : 'paid';
        return md5(implode('|', [
            $isFree,
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
