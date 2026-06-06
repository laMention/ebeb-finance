import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type OperationItem = {
    id: string;
    type_operation: string;
    montant: string;
    statut: 'EN_ATTENTE' | 'SUCCES' | 'ECHEC' | null;
    created_at: string;
    user?: {
        nom?: string;
        prenom?: string;
    };
    type_cotisation?: {
        code?: string;
        libelle?: string;
    } | null;
    paiement_entrant?: {
        reference_externe?: string;
    } | null;
};

type Props = {
    items: {
        data: OperationItem[];
    };
};

const badgeVariant = (status: OperationItem['statut']) => {
    if (status === 'SUCCES') return 'default';
    if (status === 'ECHEC') return 'destructive';
    return 'secondary';
};

export default function AdminOperationsIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - Operations" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Operations financieres</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4">Date</th>
                                        <th className="py-2 pr-4">Utilisateur</th>
                                        <th className="py-2 pr-4">Type</th>
                                        <th className="py-2 pr-4">Cotisation</th>
                                        <th className="py-2 pr-4">Montant</th>
                                        <th className="py-2 pr-4">Reference paiement</th>
                                        <th className="py-2 pr-4">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 pr-4">{new Date(item.created_at).toLocaleString()}</td>
                                            <td className="py-2 pr-4">
                                                {[item.user?.prenom, item.user?.nom].filter(Boolean).join(' ') || '-'}
                                            </td>
                                            <td className="py-2 pr-4">{item.type_operation}</td>
                                            <td className="py-2 pr-4">{item.type_cotisation?.code ?? '-'}</td>
                                            <td className="py-2 pr-4">{item.montant}</td>
                                            <td className="py-2 pr-4">{item.paiement_entrant?.reference_externe ?? '-'}</td>
                                            <td className="py-2 pr-4">
                                                <Badge variant={badgeVariant(item.statut)}>{item.statut ?? 'EN_ATTENTE'}</Badge>
                                            </td>
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

AdminOperationsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'Operations', href: '/admin/operations' },
    ],
};
