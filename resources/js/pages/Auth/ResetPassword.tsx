import { type FormEvent, type ReactElement } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface ResetPasswordLinks {
    passwordStore: string;
    login: string;
}

interface ResetPasswordPageProps extends SharedPageProps {
    email: string;
    token: string;
    links: ResetPasswordLinks;
}

interface ResetPasswordFormData {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function ResetPassword(): ReactElement {
    const { email, token, links } = usePage<ResetPasswordPageProps>().props;

    const { data, setData, post, errors, processing } = useForm<ResetPasswordFormData>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.passwordStore);
    };

    return (
        <GuestLayout title="Alege o parolă nouă">
            <h1 className="font-sans font-bold text-2xl">Alege o parolă nouă</h1>
            <p className="mt-2 text-sm text-dim">Introdu adresa de email și noua parolă.</p>

            <form onSubmit={handleSubmit} className="mt-6">
                <input type="hidden" name="token" value={data.token} />

                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        className="mt-2 block w-full"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        required
                        autoFocus
                        autoComplete="username"
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-5">
                    <InputLabel htmlFor="password" value="Parolă nouă" />
                    <TextInput
                        id="password"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        required
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-5">
                    <InputLabel htmlFor="password_confirmation" value="Confirmă parola" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                        required
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-7">
                    <PrimaryButton className="w-full py-3" processing={processing}>
                        Resetează parola
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
