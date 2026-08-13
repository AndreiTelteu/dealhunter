import { useState, type FormEvent, type ReactElement } from 'react';
import { router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import { ConfirmDialog } from '../../components/ui/ConfirmDialog';
import { DangerButton } from '../../components/ui/DangerButton';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface ProfileEditLinks {
    updateProfile: string;
    updatePassword: string;
    destroy: string;
    verificationSend: string;
}

interface ProfileEditPageProps extends SharedPageProps {
    mustVerifyEmail: boolean;
    status: string | null;
    links: ProfileEditLinks;
}

interface ProfileInformationFormData {
    name: string;
    email: string;
}

interface UpdatePasswordFormData {
    current_password: string;
    password: string;
    password_confirmation: string;
}

interface DeleteUserFormData {
    password: string;
}

/** Inline "Salvat." status line, matching the Blade auto-dismiss message. */
function SavedStatus(): ReactElement {
    return (
        <p className="flex items-center gap-2 text-sm text-em-green">
            <span
                className="spec-line inline-block h-3 w-px bg-[#7dffa8] text-[#7dffa8]"
                aria-hidden="true"
            ></span>
            Salvat.
        </p>
    );
}

function UpdateProfileInformationForm({
    user,
    mustVerifyEmail,
    status,
    links,
}: {
    user: NonNullable<ProfileEditPageProps['auth']['user']>;
    mustVerifyEmail: boolean;
    status: string | null;
    links: ProfileEditLinks;
}): ReactElement {
    const { data, setData, patch, errors, processing } = useForm<ProfileInformationFormData>({
        name: user.name,
        email: user.email,
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        patch(links.updateProfile);
    };

    return (
        <section>
            <header>
                <p className="placard text-[0.6rem]">Date personale</p>
                <h2 className="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">Datele tale</h2>

                <p className="mt-1.5 max-w-xl text-sm text-dim">
                    Numele și adresa de email folosite pentru cont.
                </p>
            </header>

            <form onSubmit={handleSubmit} className="mt-6 space-y-6">
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

                <div>
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

                    {mustVerifyEmail && (
                        <div className="mt-3 border border-[#59e3ff]/25 bg-[#06080a] px-4 py-3">
                            <p className="text-sm text-dim">
                                Adresa ta de email nu este verificată.
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(links.verificationSend, {}, { preserveScroll: true })
                                    }
                                    className="ml-1 text-beam underline decoration-[#59e3ff]/40 underline-offset-4 transition-colors hover:text-[#eaf4f6] focus-ring rounded-sm"
                                >
                                    Trimite din nou emailul de verificare.
                                </button>
                            </p>

                            {status === 'verification-link-sent' && (
                                <p className="mt-2 flex items-center gap-2 text-sm font-medium text-em-green">
                                    <span
                                        className="spec-line inline-block h-3 w-px bg-[#7dffa8] text-[#7dffa8]"
                                        aria-hidden="true"
                                    ></span>
                                    Un nou link de verificare a fost trimis.
                                </p>
                            )}
                        </div>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton processing={processing}>Salvează datele</PrimaryButton>

                    {status === 'profile-updated' && <SavedStatus />}
                </div>
            </form>
        </section>
    );
}

function UpdatePasswordForm({
    status,
    links,
}: {
    status: string | null;
    links: ProfileEditLinks;
}): ReactElement {
    const { data, setData, put, reset, errors, processing } = useForm<UpdatePasswordFormData>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        put(links.updatePassword, {
            preserveScroll: true,
            onSuccess: () => reset('current_password', 'password', 'password_confirmation'),
        });
    };

    return (
        <section>
            <header>
                <p className="placard text-[0.6rem]">Acces</p>
                <h2 className="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">Schimbă parola</h2>

                <p className="mt-1.5 max-w-xl text-sm text-dim">
                    Alege o parolă lungă, unică și greu de ghicit.
                </p>
            </header>

            <form onSubmit={handleSubmit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="update_password_current_password" value="Parola actuală" />
                    <TextInput
                        id="update_password_current_password"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.current_password}
                        onChange={(event) => setData('current_password', event.target.value)}
                        autoComplete="current-password"
                    />
                    <InputError message={errors.current_password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="update_password_password" value="Parola nouă" />
                    <TextInput
                        id="update_password_password"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="update_password_password_confirmation" value="Confirmă parola nouă" />
                    <TextInput
                        id="update_password_password_confirmation"
                        type="password"
                        className="mt-2 block w-full"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton processing={processing}>Actualizează parola</PrimaryButton>

                    {status === 'password-updated' && <SavedStatus />}
                </div>
            </form>
        </section>
    );
}

function DeleteUserForm({ links }: { links: ProfileEditLinks }): ReactElement {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const { data, setData, delete: destroy, errors, processing } = useForm<DeleteUserFormData>({
        password: '',
    });

    const handleDelete = (): void => {
        destroy(links.destroy, {
            preserveScroll: true,
            onSuccess: () => setConfirmingDeletion(false),
            onError: () => setConfirmingDeletion(true),
        });
    };

    const closeModal = (): void => {
        setConfirmingDeletion(false);
    };

    return (
        <section className="space-y-6">
            <header>
                <p className="font-mono text-[0.6rem] uppercase text-em-red">Zonă ireversibilă</p>
                <h2 className="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">Șterge contul</h2>

                <p className="mt-1.5 max-w-xl text-sm text-dim">
                    Toate datele contului vor fi șterse definitiv. Păstrează înainte informațiile de care ai nevoie.
                </p>
            </header>

            <DangerButton type="button" onClick={() => setConfirmingDeletion(true)}>
                Șterge contul
            </DangerButton>

            <ConfirmDialog
                show={confirmingDeletion}
                onClose={closeModal}
                onConfirm={handleDelete}
                kicker="Confirmare"
                title="Ștergi definitiv contul?"
                description="Această acțiune nu poate fi anulată. Introdu parola pentru confirmare."
                cancelLabel="Renunță"
                confirmLabel="Șterge definitiv"
                processing={processing}
            >
                <div className="mt-6">
                    <InputLabel htmlFor="deletion-password" value="Parolă" className="sr-only" />
                    <TextInput
                        id="deletion-password"
                        type="password"
                        className="mt-1 block w-full sm:w-3/4"
                        placeholder="Parolă"
                        value={data.password}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                    <InputError message={errors.password} className="mt-2" />
                </div>
            </ConfirmDialog>
        </section>
    );
}

export default function ProfileEdit(): ReactElement {
    const { auth, mustVerifyEmail, status, links } = usePage<ProfileEditPageProps>().props;
    const user = auth.user;

    const header = (
        <div className="flex flex-col gap-1.5">
            <p className="placard text-[0.6rem]">Cont</p>
            <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Profil</h2>
        </div>
    );

    return (
        <AppLayout title="Profil" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="relative overflow-hidden border border-hairline bg-bench">
                        <div
                            className="pointer-events-none absolute inset-x-0 top-0 h-px bg-[#59e3ff]/45"
                            aria-hidden="true"
                        ></div>
                        <div
                            className="pointer-events-none absolute left-0 top-0 h-full w-px bg-[#59e3ff]/35"
                            aria-hidden="true"
                        ></div>

                        <div className="border-b border-hairline px-5 py-5 sm:px-8 sm:py-6">
                            <p className="placard text-[0.6rem]">Setări cont</p>
                            <p className="mt-2 max-w-2xl text-sm text-dim">
                                Actualizează datele de acces și informațiile personale.
                            </p>
                        </div>

                        {user && (
                            <div className="divide-y divide-[#1c242a]">
                                <div className="px-5 py-7 sm:px-8 sm:py-9">
                                    <div className="max-w-2xl">
                                        <UpdateProfileInformationForm
                                            user={user}
                                            mustVerifyEmail={mustVerifyEmail}
                                            status={status}
                                            links={links}
                                        />
                                    </div>
                                </div>

                                <div className="px-5 py-7 sm:px-8 sm:py-9">
                                    <div className="max-w-2xl">
                                        <UpdatePasswordForm status={status} links={links} />
                                    </div>
                                </div>

                                <div className="bg-[#06080a]/45 px-5 py-7 sm:px-8 sm:py-9">
                                    <div className="max-w-2xl">
                                        <DeleteUserForm links={links} />
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
