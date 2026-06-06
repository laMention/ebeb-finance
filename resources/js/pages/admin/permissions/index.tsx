import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type PermissionItem = {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
};

type Props = {
    items: {
        data: PermissionItem[];
    };
};

export default function AdminPermissionsIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - Permissions" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Permissions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-2 pr-4">Nom</th>
                                        <th className="py-2 pr-4">Guard</th>
                                        <th className="py-2 pr-4">Creation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="border-b">
                                            <td className="py-2 pr-4">{item.name}</td>
                                            <td className="py-2 pr-4">{item.guard_name}</td>
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

AdminPermissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'Permissions', href: '/admin/permissions' },
    ],
};
