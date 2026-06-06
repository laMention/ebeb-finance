import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type KycItem = {
    id: string;
    type_document: string;
    numero_document: string | null;
    statut: 'EN_ATTENTE' | 'VALIDE' | 'REJETE';
    created_at: string;
    user?: {
        nom?: string;
        prenom?: string;
        telephone?: string;
    };
};

type Props = {
    items: {
        data: KycItem[];
    };
};

const badgeVariant = (status: KycItem['statut']) => {
    if (status === 'VALIDE') return 'default';
    if (status === 'REJETE') return 'destructive';
    return 'secondary';
};

export default function AdminKycIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - KYC" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Dossiers KYC</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4">Utilisateur</th>
                                        <th className="py-2 pr-4">Telephone</th>
                                        <th className="py-2 pr-4">Type</th>
                                        <th className="py-2 pr-4">Numero</th>
                                        <th className="py-2 pr-4">Statut</th>
                                        <th className="py-2 pr-4">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 pr-4">
                                                {[item.user?.prenom, item.user?.nom].filter(Boolean).join(' ') || '-'}
                                            </td>
                                            <td className="py-2 pr-4">{item.user?.telephone ?? '-'}</td>
                                            <td className="py-2 pr-4">{item.type_document}</td>
                                            <td className="py-2 pr-4">{item.numero_document ?? '-'}</td>
                                            <td className="py-2 pr-4">
                                                <Badge variant={badgeVariant(item.statut)}>{item.statut}</Badge>
                                            </td>
                                            <td className="py-2 pr-4">{new Date(item.created_at).toLocaleString()}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminKycIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'KYC', href: '/admin/kyc' },
    ],
};
