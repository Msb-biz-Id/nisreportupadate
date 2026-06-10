import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function formatRupiah(value) {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(value));
}

export function formatDate(value, opts = {}) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...opts,
    }).format(new Date(value));
}

export function formatDateTime(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function initials(name) {
    if (!name) return '?';
    return name
        .split(' ')
        .map((s) => s[0])
        .filter(Boolean)
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

export const ROLE_LABELS = {
    superadmin: 'Superadmin',
    owner: 'Owner',
    admin_brand: 'Admin Brand',
    admin_reseller: 'Admin Reseller',
    admin_produksi: 'Admin Produksi',
    admin_keuangan: 'Admin Keuangan',
};

export function roleLabel(slug) {
    if (!slug) return '';
    if (ROLE_LABELS[slug]) return ROLE_LABELS[slug];
    return slug
        .replace(/[_-]/g, ' ')
        .split(' ')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}
