<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\StorePurchasingVendorContactRequest;
use App\Http\Requests\Purchasing\StorePurchasingVendorRequest;
use App\Http\Requests\Purchasing\UpdatePurchasingVendorRequest;
use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingVendor;
use App\Models\Purchasing\PurchasingVendorContact;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingVendorController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('purchasing.view');

        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');

        $vendors = PurchasingVendor::query()
            ->with(['contacts', 'primaryContact'])
            ->withCount(['orders', 'assets'])
            ->when($search, function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('vendor_code', 'like', "%{$s}%")
                        ->orWhere('item_name', 'like', "%{$s}%")
                        ->orWhereHas('contacts', function ($cq) use ($s) {
                            $cq->where('pic_name', 'like', "%{$s}%")
                                ->orWhere('phone', 'like', "%{$s}%")
                                ->orWhere('email', 'like', "%{$s}%");
                        });
                });
            })
            ->when($category, fn ($q, $c) => $q->where('category', $c))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('is_active', $status === 'active' || $status === '1'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $vendorCategories = PurchasingMasterOption::getOptions('vendor_type');
        $itemCategories = PurchasingMasterOption::getOptions('item_category');

        return Inertia::render('Purchasing/Vendors/Index', [
            'vendors' => $vendors,
            'vendorCategories' => $vendorCategories,
            'itemCategories' => $itemCategories,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
            ],
        ]);
    }

    public function store(StorePurchasingVendorRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $contactsData = $validated['contacts'] ?? [];
        unset($validated['contacts']);

        /** @var PurchasingVendor $vendor */
        $vendor = DB::transaction(function () use ($validated, $contactsData) {
            $vendor = PurchasingVendor::create($validated);

            if (!empty($contactsData)) {
                $hasPrimary = false;
                foreach ($contactsData as $index => $contact) {
                    $isPrimary = !empty($contact['is_primary']) || ($index === 0 && !$hasPrimary);
                    if ($isPrimary) {
                        $hasPrimary = true;
                    }

                    $vendor->contacts()->create([
                        'pic_name' => $contact['pic_name'],
                        'role_title' => $contact['role_title'] ?? null,
                        'phone' => $contact['phone'],
                        'email' => $contact['email'] ?? null,
                        'is_primary' => $isPrimary,
                        'notes' => $contact['notes'] ?? null,
                    ]);
                }
            }

            return $vendor;
        });

        ActivityLogger::log('create', 'purchasing', $vendor, "Mendaftarkan vendor baru {$vendor->name} ({$vendor->vendor_code})");

        return back()->with('success', "Vendor \"{$vendor->name}\" berhasil didaftarkan.");
    }

    public function update(UpdatePurchasingVendorRequest $request, PurchasingVendor $vendor): RedirectResponse
    {
        $validated = $request->validated();
        $contactsData = $validated['contacts'] ?? null;
        unset($validated['contacts']);

        DB::transaction(function () use ($vendor, $validated, $contactsData) {
            $vendor->update($validated);

            if ($contactsData !== null) {
                $existingIds = [];
                $hasPrimary = false;

                foreach ($contactsData as $index => $contact) {
                    $isPrimary = !empty($contact['is_primary']) || ($index === 0 && !$hasPrimary);
                    if ($isPrimary) {
                        $hasPrimary = true;
                    }

                    if (!empty($contact['id'])) {
                        $c = $vendor->contacts()->find($contact['id']);
                        if ($c) {
                            $c->update([
                                'pic_name' => $contact['pic_name'],
                                'role_title' => $contact['role_title'] ?? null,
                                'phone' => $contact['phone'],
                                'email' => $contact['email'] ?? null,
                                'is_primary' => $isPrimary,
                                'notes' => $contact['notes'] ?? null,
                            ]);
                            $existingIds[] = $c->id;
                            continue;
                        }
                    }

                    $newC = $vendor->contacts()->create([
                        'pic_name' => $contact['pic_name'],
                        'role_title' => $contact['role_title'] ?? null,
                        'phone' => $contact['phone'],
                        'email' => $contact['email'] ?? null,
                        'is_primary' => $isPrimary,
                        'notes' => $contact['notes'] ?? null,
                    ]);
                    $existingIds[] = $newC->id;
                }

                // Hapus kontak yang dibuang dari form
                $vendor->contacts()->whereNotIn('id', $existingIds)->delete();
            }
        });

        ActivityLogger::log('update', 'purchasing', $vendor, "Memperbarui informasi vendor {$vendor->name} ({$vendor->vendor_code})");

        return back()->with('success', "Informasi vendor \"{$vendor->name}\" berhasil diperbarui.");
    }

    public function destroy(PurchasingVendor $vendor): RedirectResponse
    {
        Gate::authorize('purchasing.manage-vendors');

        $name = $vendor->name;
        $vendor->delete();

        ActivityLogger::log('delete', 'purchasing', $vendor, "Menghapus vendor {$name}");

        return back()->with('success', "Vendor \"{$name}\" berhasil dihapus.");
    }

    public function addContact(StorePurchasingVendorContactRequest $request, PurchasingVendor $vendor): RedirectResponse
    {
        $validated = $request->validated();

        if ($vendor->contacts()->count() === 0 || !empty($validated['is_primary'])) {
            $vendor->contacts()->update(['is_primary' => false]);
            $validated['is_primary'] = true;
        }

        $contact = $vendor->contacts()->create($validated);

        ActivityLogger::log('vendor-contact', 'purchasing', $vendor, "Menambahkan kontak PIC {$contact->pic_name} ({$contact->phone}) pada vendor {$vendor->name}");

        return back()->with('success', "Kontak PIC \"{$contact->pic_name}\" berhasil ditambahkan.");
    }

    public function deleteContact(PurchasingVendor $vendor, PurchasingVendorContact $contact): RedirectResponse
    {
        Gate::authorize('purchasing.manage-vendors');

        $wasPrimary = $contact->is_primary;
        $name = $contact->pic_name;
        $contact->delete();

        if ($wasPrimary) {
            $remaining = $vendor->contacts()->first();
            if ($remaining) {
                $remaining->update(['is_primary' => true]);
            }
        }

        ActivityLogger::log('vendor-contact', 'purchasing', $vendor, "Menghapus kontak PIC {$name} dari vendor {$vendor->name}");

        return back()->with('success', "Kontak PIC \"{$name}\" berhasil dihapus.");
    }

    public function setPrimaryContact(PurchasingVendor $vendor, PurchasingVendorContact $contact): RedirectResponse
    {
        Gate::authorize('purchasing.manage-vendors');

        DB::transaction(function () use ($vendor, $contact) {
            $vendor->contacts()->update(['is_primary' => false]);
            $contact->update(['is_primary' => true]);
        });

        ActivityLogger::log('vendor-contact', 'purchasing', $vendor, "Menetapkan PIC {$contact->pic_name} sebagai kontak utama vendor {$vendor->name}");

        return back()->with('success', "PIC \"{$contact->pic_name}\" berhasil dijadikan Kontak Utama.");
    }
}
