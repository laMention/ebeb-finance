import { Head } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type RoleItem = {
    id: number;
    name: string;
    guard_name: string;
    permissions_count: number;
    permissions?: Array<{ id: number; name: string }>;
};

type Props = {
    items: RoleItem[];
};

export default function AdminRolesIndex({ items }: Props) {
    return (
        <>
            <Head title="Admin - Roles" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Roles administrateurs</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            {items.map((role) => (
                                <div key={role.id} className="rounded-lg border p-4">
                                    <div className="mb-2 flex items-center justify-between gap-2">
                                        <h3 className="font-semibold">{role.name}</h3>
                                        <Badge variant="secondary">{role.permissions_count} permissions</Badge>
                                    </div>
                                    <p className="mb-3 text-xs text-muted-foreground">Guard: {role.guard_name}</p>
                                    <div className="flex flex-wrap gap-2">
                                        {(role.permissions ?? []).map((permission) => (
                                            <Badge key={permission.id} variant="outline">
                                                {permission.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Admin Dashboard', href: '/admin/dashboard' },
        { title: 'Roles', href: '/admin/roles' },
    ],
};
