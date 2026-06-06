import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    stats: {
        users: number;
        kycPending: number;
        operationsToday: number;
        paiementsPending: number;
    };
};

export default function AdminDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Admin Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Utilisateurs</CardTitle>
                            <CardDescription>Total inscrits</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.users}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>KYC en attente</CardTitle>
                            <CardDescription>Dossiers a traiter</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.kycPending}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Operations du jour</CardTitle>
                            <CardDescription>Activite quotidienne</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.operationsToday}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Paiements en attente</CardTitle>
                            <CardDescription>A verifier</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.paiementsPending}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Admin Dashboard',
            href: '/admin/dashboard',
        },
    ],
};
