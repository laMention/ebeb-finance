import { Head, useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

export default function AdminLogin() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        post('/admin/login');
    };

    return (
        <>
            <Head title="Connexion admin" />

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email administrateur</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            autoComplete="email"
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            placeholder="admin@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Mot de passe</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            autoComplete="current-password"
                            placeholder="Mot de passe"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            checked={data.remember}
                            onCheckedChange={(checked) => setData('remember', Boolean(checked))}
                        />
                        <Label htmlFor="remember">Se souvenir de moi</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing && <Spinner />}
                        Se connecter
                    </Button>
                </div>
            </form>
        </>
    );
}

AdminLogin.layout = {
    title: "Connexion à l'espace admin",
    description: 'Accès réservé aux administrateurs',
};
