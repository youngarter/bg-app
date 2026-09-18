/**
 * Format tantièmes relative to the residence total tantièmes.
 * Example: 150 tantièmes on a total of 8 000 total_tantiemes = 1,875 %
 */
export function formatTantiemesPercentage(
    tantiemes: number,
    totalTantiemes: number,
): string {
    if (!totalTantiemes || totalTantiemes <= 0) {
        return '0,00 %';
    }

    const percentage = (tantiemes / totalTantiemes) * 100;
    // Format up to 3 decimals if needed, or 2 minimum
    const formatted = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 3,
    }).format(percentage);

    return `${formatted} %`;
}

/**
 * Format a lot ownership quote-part (expressed in basis points / dix-millièmes, 10 000 = 100 %).
 * Example: 5000 bp = 50,00 %
 */
export function formatOwnershipQuotePart(quotePart: number): string {
    const percentage = (quotePart || 0) / 100;

    const formatted = new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(percentage);

    return `${formatted} %`;
}

/**
 * Format a date string (YYYY-MM-DD) to French display (JJ/MM/AAAA).
 */
export function formatDate(date: string | null | undefined): string {
    if (!date) {
        return '—';
    }

    const parts = date.split('-');

    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    const parsed = new Date(date);

    if (isNaN(parsed.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(parsed);
}

/**
 * Format an integer with French thousand separator.
 */
export function formatInteger(val: number | null | undefined): string {
    if (val === null || val === undefined) {
        return '0';
    }

    return new Intl.NumberFormat('fr-FR').format(val);
}

/**
 * Format a decimal number in French notation (comma separator).
 * Example: 87.5 -> "87,5"
 */
export function formatDecimal(
    val: number | string | null | undefined,
    maxDecimals = 2,
): string {
    if (val === null || val === undefined || val === '') {
        return '0';
    }

    const numeric =
        typeof val === 'number' ? val : Number.parseFloat(String(val));

    if (Number.isNaN(numeric)) {
        return '0';
    }

    return new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: maxDecimals,
    }).format(numeric);
}
