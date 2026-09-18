interface AppBrandProps {
    compact?: boolean;
    className?: string;
}

/**
 * Lockup officiel Bayan Gestion conforme à la charte systeme-visuel.html.
 *
 * Le monogramme « b » est associé au mot-symbole en Poppins 700 et à la mention
 * de pôle « GESTION » en Poppins 600 espacée orange (#FF5500).
 * La hauteur du monogramme suit parfaitement la hauteur du bloc textuel.
 */
export function AppBrand({ compact = false, className = '' }: AppBrandProps) {
    if (compact) {
        return (
            <div className={`flex items-center justify-center py-1 ${className}`}>
                <img
                    src="/logo.png"
                    alt="Bayan"
                    className="size-8 object-contain select-none transition-transform hover:scale-105"
                />
            </div>
        );
    }

    return (
        <div className={`flex items-center gap-3 select-none ${className}`}>
            <img
                src="/logo.png"
                alt="Bayan"
                className="size-9 shrink-0 object-contain drop-shadow-2xs transition-transform hover:scale-105"
            />
            <div className="flex flex-col justify-center leading-none">
                <span className="font-display text-xl font-bold tracking-tight text-foreground">
                    bayan
                </span>
                <span className="mt-1 font-display text-[9px] font-semibold uppercase tracking-[0.34em] text-brand-orange">
                    GESTION
                </span>
            </div>
        </div>
    );
}
