export type Clinic = {
    name: string;
    short_name: string;
    tagline: string;
    logo: string;
    logo_fallback: string;
    colors: Record<string, string>;
    contact: { address: string; phone: string; email: string };
    social: Record<'facebook' | 'instagram' | 'tiktok', string | null>;
    currency: string;
    currency_symbol: string;
    timezone: string;
    hours: Record<string, string>;
};

export type Module =
    | 'booking'
    | 'crm'
    | 'pos'
    | 'inventory'
    | 'accounting'
    | 'payroll'
    | 'ai'
    | 'multi_branch';
