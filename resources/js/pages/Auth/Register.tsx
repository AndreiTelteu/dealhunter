import { type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface RegisterLinks {
    register: string;
    login: string;
}

interface RegisterPageProps extends SharedPageProps {
    links: RegisterLinks;
}

interface RegisterFormData {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
}

export default function Register(): ReactElement {
    const { links } = usePage<RegisterPageProps>().props;

    const { data, setData, post, reset, errors, processing } = useForm<RegisterFormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.register, {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout title="Creează cont">
            <h1 className="font-sans font-bold text-2xl">Creează cont</h1>
            <p className="mt-2 text-sm text-dim">Îți faci cont în două minute și salvezi prima căutare.</p>

            <form onSubmit={handleSubmit} className="mt-6">
                <div>
                    <InputLabel htmlFor="name" value="Nume" />
                    <TextInput
                        id="name"
                        type="text"
                        className="mt-2 block w-full"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        required
                        autoFocus
                        autoComplete="name"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-5">
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        className="mt-2 block w-full"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        required
                        autoComplete="username"
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-5">
                    <InputLabel htmlFor="password" value="Parolă" />
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
                        Creează cont
                    </PrimaryButton>
                </div>

                <div className="mt-6 border-t border-hairline pt-5 text-center sm:text-left">
                    <Link
                        href={links.login}
                        className="focus-ring rounded-sm text-sm text-dim transition-colors hover:text-beam"
                    >
                        Ai deja cont? <span className="text-beam">Autentifică-te</span>
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
