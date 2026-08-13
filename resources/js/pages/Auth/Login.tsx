import { type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface LoginLinks {
    login: string;
    register: string;
    passwordRequest: string;
}

interface LoginPageProps extends SharedPageProps {
    status: string | null;
    canResetPassword: boolean;
    links: LoginLinks;
}

interface LoginFormData {
    email: string;
    password: string;
    remember: boolean;
}

export default function Login(): ReactElement {
    const { status, canResetPassword, links } = usePage<LoginPageProps>().props;

    const { data, setData, post, reset, errors, processing } = useForm<LoginFormData>({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.login, {
            preserveScroll: true,
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout title="Autentificare">
            <h1 className="font-sans font-bold text-2xl">Autentificare</h1>
            <p className="mt-2 text-sm text-dim">Intră în cont ca să vezi anunțurile urmărite.</p>

            {status && (
                <p className="mt-6 border border-hairline border-l-2 border-l-[#7dffa8] bg-[#06080a] px-4 py-3 text-sm font-medium text-em-green">
                    {status}
                </p>
            )}

            <form onSubmit={handleSubmit} className="mt-6">
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
                    <InputLabel htmlFor="password" value="Parolă" />
                    <TextInput
                        id="password"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        required
                        autoComplete="current-password"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-5">
                    <label htmlFor="remember_me" className="inline-flex cursor-pointer items-center gap-2">
                        <input
                            id="remember_me"
                            type="checkbox"
                            checked={data.remember}
                            onChange={(event) => setData('remember', event.target.checked)}
                            className="rounded-sm border-hairline bg-[#06080a] text-[#59e3ff] shadow-none focus:ring-2 focus:ring-[#59e3ff]/30 focus:ring-offset-0"
                        />
                        <span className="text-sm text-dim">Ține-mă minte</span>
                    </label>
                </div>

                <div className="mt-7">
                    <PrimaryButton className="w-full py-3" processing={processing}>
                        Autentificare
                    </PrimaryButton>
                </div>

                <div className="mt-6 flex flex-col items-center justify-between gap-3 border-t border-hairline pt-5 sm:flex-row">
                    {canResetPassword && (
                        <Link
                            href={links.passwordRequest}
                            className="focus-ring rounded-sm text-sm text-dim transition-colors hover:text-beam"
                        >
                            Ai uitat parola?
                        </Link>
                    )}
                    <Link
                        href={links.register}
                        className="focus-ring rounded-sm text-sm text-dim transition-colors hover:text-beam"
                    >
                        Nu ai cont? <span className="text-beam">Creează unul</span>
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
