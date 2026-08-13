import { type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface ForgotPasswordLinks {
    login: string;
    passwordEmail: string;
}

interface ForgotPasswordPageProps extends SharedPageProps {
    status: string | null;
    links: ForgotPasswordLinks;
}

interface ForgotPasswordFormData {
    email: string;
}

export default function ForgotPassword(): ReactElement {
    const { status, links } = usePage<ForgotPasswordPageProps>().props;

    const { data, setData, post, errors, processing } = useForm<ForgotPasswordFormData>({
        email: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.passwordEmail);
    };

    return (
        <GuestLayout title="Ai uitat parola?">
            <h1 className="font-sans font-bold text-2xl">Ai uitat parola?</h1>
            <p className="mt-2 text-sm text-dim leading-relaxed">
                Scrie adresa ta de email și îți trimitem un link cu care îți alegi o parolă nouă.
            </p>

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
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-7">
                    <PrimaryButton className="w-full py-3" processing={processing}>
                        Trimite linkul de resetare
                    </PrimaryButton>
                </div>

                <div className="mt-6 border-t border-hairline pt-5 text-center sm:text-left">
                    <Link
                        href={links.login}
                        className="focus-ring rounded-sm text-sm text-dim transition-colors hover:text-beam"
                    >
                        &larr; Înapoi la autentificare
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
