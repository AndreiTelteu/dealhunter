/**
 * Formatting helpers shared across pages.
 * Mirror the Blade behaviour (RON prices, relative "acum X" labels are
 * already computed server-side; the client only formats amounts).
 */

/**
 * Format a deal price the way the Blade templates render it:
 * "1.250 lei" / "€450". Null amounts render as "Preț la cerere".
 */
export function formatPrice(amount: number | null, currency: string | null): string {
    if (amount === null || Number.isNaN(amount)) {
        return 'Preț la cerere';
    }

    const value = new Intl.NumberFormat('ro-RO', {
        minimumFractionDigits: amount % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    }).format(amount);

    const normalizedCurrency = (currency ?? 'lei').toLowerCase();

    if (normalizedCurrency === 'eur' || normalizedCurrency === '€') {
        return `€${value}`;
    }

    if (normalizedCurrency === 'usd' || normalizedCurrency === '$') {
        return `$${value}`;
    }

    return `${value} lei`;
}

/**
 * Format an amount with Romanian thousands separators the way the
 * Blade templates render it: `number_format($amount, 0, ',', '.')`
 * produces "1.250".
 */
export function formatNumber(amount: number): string {
    return new Intl.NumberFormat('ro-RO', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}

/**
 * Shorten long titles the way Str::limit($title, 60) does server-side.
 */
export function truncate(value: string, length = 60): string {
    if (value.length <= length) {
        return value;
    }

    return `${value.slice(0, length).trimEnd()}…`;
}
