import { usePage } from '@inertiajs/react';
import type { LicenseData, Residence, AccessibleResidence } from '@/types/bayan';

export interface PageSharedProps {
    residence: Residence | null;
    accessibleResidences: AccessibleResidence[];
    currentRole: string | null;
    license: LicenseData | null;
    canWrite: boolean;
    [key: string]: unknown;
}

export function useCanWrite() {
    const { props } = usePage<{
        residence?: Residence | null;
        accessibleResidences?: AccessibleResidence[];
        currentRole?: string | null;
        license?: LicenseData | null;
        canWrite?: boolean;
    }>();

    const license = props.license ?? null;
    const canWrite = props.canWrite ?? true;
    const isReadOnly = license?.is_read_only ?? false;
    const isGrace = license?.is_grace ?? false;
    const isSuspended = license?.is_suspended ?? false;

    const disabledReason = isReadOnly
        ? 'La licence de cette résidence est en lecture seule. La saisie et les modifications sont désactivées.'
        : isSuspended
          ? 'La licence de cette résidence est suspendue.'
          : undefined;

    return {
        canWrite,
        isReadOnly,
        isGrace,
        isSuspended,
        license,
        disabledReason,
        residence: props.residence ?? null,
        accessibleResidences: props.accessibleResidences ?? [],
        currentRole: props.currentRole ?? null,
    };
}
