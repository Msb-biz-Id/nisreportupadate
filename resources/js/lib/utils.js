import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
import React from 'react';

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

/**
 * Detects CJK and Arabic characters in text and wraps them in spans with specific font-family classes.
 * Useful for browser rendering to ensure high-fidelity typography.
 *
 * @param {string|null|undefined} text
 * @returns {React.ReactNode[]|string}
 */
export function renderFormattedText(text) {
    if (text === null || text === undefined || text === '') return '';
    const textStr = String(text);

    // Regex matching CJK, Arabic, and Javanese unicode blocks
    const regex = /([\u3000-\u303F\u3040-\u309F\u30A0-\u30FF\uFF00-\uFFEF\u4E00-\u9FAF\u3400-\u4DBF]+)|([\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]+)|([\uA980-\uA9DF]+)/g;

    const parts = [];
    let lastIndex = 0;
    let match;

    // Reset regex state
    regex.lastIndex = 0;

    while ((match = regex.exec(textStr)) !== null) {
        const index = match.index;
        const matchedStr = match[0];

        // Add preceding normal text
        if (index > lastIndex) {
            parts.push(textStr.substring(lastIndex, index));
        }

        if (match[1]) {
            // CJK characters
            parts.push(
                React.createElement(
                    'span',
                    { key: index, className: 'cjk-font' },
                    matchedStr
                )
            );
        } else if (match[2]) {
            // Arabic characters (native browser layout handles bidirectional and shaping, but needs the font)
            parts.push(
                React.createElement(
                    'span',
                    { key: index, className: 'arabic-font', dir: 'rtl' },
                    matchedStr
                )
            );
        } else if (match[3]) {
            // Javanese characters
            parts.push(
                React.createElement(
                    'span',
                    { key: index, className: 'javanese-font' },
                    matchedStr
                )
            );
        }

        lastIndex = regex.lastIndex;
    }

    if (lastIndex < textStr.length) {
        parts.push(textStr.substring(lastIndex));
    }

    return parts.length > 0 ? parts : textStr;
}
