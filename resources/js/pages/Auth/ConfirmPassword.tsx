import { type FormEvent, type ReactElement } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface ConfirmPasswordLinks {
    confirm: string;
}

interface ConfirmPasswordPageProps extends SharedPageProps {
    links: ConfirmPasswordLinks;
}

interface ConfirmPasswordFormData {
    password: string;
}

export default function ConfirmPassword(): ReactElement {
    const { links } = usePage<ConfirmPasswordPageProps>().props;

    const { data, setData, post, errors, processing } = useForm<ConfirmPasswordFormData>({
        password: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.confirm);
    };

    return (
        <GuestLayout title="Confirmă parola">
            <h1 className="font-sans font-bold text-2xl">Confirmă parola</h1>
            <p className="mt-2 text-sm text-dim leading-relaxed">
                Aceasta este o zonă securizată. Confirmă parola înainte de a continua.
            </p>

            <form onSubmit={handleSubmit} className="mt-6">
                <div>
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

                <div className="mt-7">
                    <PrimaryButton className="w-full py-3" processing={processing}>
                        Confirmă
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
