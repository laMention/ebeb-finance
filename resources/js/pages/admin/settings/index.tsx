import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type ParametreItem = {
    id: string;
    cle: string;
    valeur: string;
    description: string | null;
    updated_at: string;
    administrateur?: {
        nom?: string;
        prenom?: string;
        email?: string;
    } | null;
};

type Props = {
    items: {
        data: ParametreItem[];
    };
};

export default function AdminSettingsIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - Parametres" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Parametres globaux</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4">Cle</th>
                                        <th className="py-2 pr-4">Valeur</th>
                                        <th className="py-2 pr-4">Description</th>
                                        <th className="py-2 pr-4">Modifie par</th>
                                        <th className="py-2 pr-4">Mis a jour le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 pr-4">{item.cle}</td>
                                            <td className="py-2 pr-4">{item.valeur}</td>
                                            <td className="py-2 pr-4">{item.description ?? '-'}</td>
                                            <td className="py-2 pr-4">
                                                {[item.administrateur?.prenom, item.administrateur?.nom].filter(Boolean).join(' ') || item.administrateur?.email || '-'}
                                            </td>
                                            <td className="py-2 pr-4">{new Date(item.updated_at).toLocaleString()}</td>
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

AdminSettingsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'Parametres', href: '/admin/settings' },
    ],
};
