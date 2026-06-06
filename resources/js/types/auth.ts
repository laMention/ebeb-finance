export type User = {
    id: string | number;
    name?: string;
    nom?: string;
    prenom?: string;
    email?: string | null;
    telephone?: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
    admin?: User | null;
    guard?: 'web' | 'admin';
    roles?: string[];
    permissions?: string[];
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
