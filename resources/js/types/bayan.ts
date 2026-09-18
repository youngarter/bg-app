export interface Residence {
    id: number;
    nom: string;
    adresse: string | null;
    ville: string | null;
    code_postal?: string | null;
    total_tantiemes: number;
}

export interface AccessibleResidence {
    id: number;
    nom: string;
    ville: string | null;
}

export interface LicenseData {
    status: 'active' | 'grace' | 'read_only' | 'suspended';
    status_label: string;
    ends_on: string | null;
    grace_days: number;
    grace_ends_on: string | null;
    can_write: boolean;
    is_grace: boolean;
    is_read_only: boolean;
    is_suspended: boolean;
}

export interface OnboardingStep {
    index: number;
    key: string;
    title: string;
    description: string;
    completed: boolean;
    href: string;
    lots_count?: number;
    has_owners?: boolean;
    sum_tantiemes?: number;
    total_tantiemes?: number;
    tantiemes_consistent?: boolean;
    discrepancy?: number;
    discrepancy_percentage?: number;
}

export interface OnboardingData {
    is_complete: boolean;
    steps: OnboardingStep[];
}
