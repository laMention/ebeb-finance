import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type AuditItem = {
    id: string;
    action: string;
    entite_cible: string;
    entite_id: string | null;
    ip_adresse: string | null;
    created_at: string;
    administrateur?: {
        nom?: string;
        prenom?: string;
        email?: string;
    };
};

type Props = {
    items: {
        data: AuditItem[];
    };
};

export default function AdminAuditIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - Audit" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Journal d'audit</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4">Date</th>
                                        <th className="py-2 pr-4">Administrateur</th>
                                        <th className="py-2 pr-4">Action</th>
                                        <th className="py-2 pr-4">Entite</th>
                                        <th className="py-2 pr-4">ID cible</th>
                                        <th className="py-2 pr-4">IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 pr-4">{new Date(item.created_at).toLocaleString()}</td>
                                            <td className="py-2 pr-4">
                                                {[item.administrateur?.prenom, item.administrateur?.nom].filter(Boolean).join(' ') || item.administrateur?.email || '-'}
                                            </td>
                                            <td className="py-2 pr-4">{item.action}</td>
                                            <td className="py-2 pr-4">{item.entite_cible}</td>
                                            <td className="py-2 pr-4">{item.entite_id ?? '-'}</td>
                                            <td className="py-2 pr-4">{item.ip_adresse ?? '-'}</td>
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

AdminAuditIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'Audit', href: '/admin/audit' },
    ],
};
