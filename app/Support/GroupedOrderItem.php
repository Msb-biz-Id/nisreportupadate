<?php

namespace App\Support;

use Illuminate\Support\Collection;
use ArrayAccess;
use JsonSerializable;

class GroupedOrderItem implements ArrayAccess, JsonSerializable
{
    public $representative;
    public $items;
    protected $combinedNamesets;

    public function __construct($representative, Collection $items)
    {
        $this->representative = $representative;
        $this->items = $items;
        
        $this->combinedNamesets = collect();
        foreach ($items as $item) {
            $isFree = false;
            if (is_array($item)) {
                $isFree = ((float) ($item['harga_satuan'] ?? 0.0) === 0.0);
                $itemNamesets = $item['namesets'] ?? [];
            } else {
                $isFree = ((float) ($item->harga_satuan ?? 0.0) === 0.0);
                $itemNamesets = $item->relationLoaded('namesets') ? $item->namesets : collect();
                if ($itemNamesets->isEmpty() && is_array($item->namesets)) {
                    $itemNamesets = collect($item->namesets);
                }
            }
            
            foreach ($itemNamesets as $ns) {
                if (is_object($ns)) {
                    $nsClone = clone $ns;
                    $nsClone->is_free = $isFree;
                    $this->combinedNamesets->push($nsClone);
                } else if (is_array($ns)) {
                    $ns['is_free'] = $isFree;
                    $this->combinedNamesets->push($ns);
                }
            }
        }
    }

    public function __get($key)
    {
        if ($key === 'quantity') {
            return $this->items->sum(fn($i) => is_array($i) ? ($i['quantity'] ?? 0) : ($i->quantity ?? 0));
        }
        if ($key === 'jml_atasan') {
            $sum = 0;
            foreach ($this->items as $i) {
                if (is_array($i)) {
                    $sum += ($i['jml_atasan'] ?: ($i['quantity'] ?? 0));
                } else {
                    $sum += ($i->jml_atasan ?: ($i->quantity ?? 0));
                }
            }
            return $sum;
        }
        if ($key === 'jml_bawahan') {
            return $this->items->sum(fn($i) => is_array($i) ? ($i['jml_bawahan'] ?? 0) : ($i->jml_bawahan ?? 0));
        }
        if ($key === 'namesets') {
            return $this->combinedNamesets;
        }

        if (is_array($this->representative)) {
            return $this->representative[$key] ?? null;
        }
        return $this->representative->$key;
    }

    public function __set($key, $value)
    {
        if (is_array($this->representative)) {
            $this->representative[$key] = $value;
        } else {
            $this->representative->$key = $value;
        }
    }

    public function __call($name, $arguments)
    {
        if (is_array($this->representative)) {
            throw new \Exception("Cannot call method {$name} on array representative");
        }
        return call_user_func_array([$this->representative, $name], $arguments);
    }

    // ArrayAccess Implementation
    public function offsetExists($offset): bool
    {
        if (in_array($offset, ['quantity', 'jml_atasan', 'jml_bawahan', 'namesets'], true)) {
            return true;
        }
        if (is_array($this->representative)) {
            return isset($this->representative[$offset]);
        }
        return isset($this->representative->$offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        if (is_array($this->representative)) {
            unset($this->representative[$offset]);
        } else {
            unset($this->representative->$offset);
        }
    }

    // JsonSerializable Implementation
    public function jsonSerialize(): mixed
    {
        $data = is_array($this->representative) ? $this->representative : $this->representative->toArray();
        $data['quantity'] = $this->offsetGet('quantity');
        $data['jml_atasan'] = $this->offsetGet('jml_atasan');
        $data['jml_bawahan'] = $this->offsetGet('jml_bawahan');
        
        $serializedNamesets = [];
        foreach ($this->combinedNamesets as $ns) {
            $serializedNamesets[] = is_array($ns) ? $ns : (is_object($ns) && method_exists($ns, 'toArray') ? $ns->toArray() : (array)$ns);
        }
        $data['namesets'] = $serializedNamesets;
        return $data;
    }
}
