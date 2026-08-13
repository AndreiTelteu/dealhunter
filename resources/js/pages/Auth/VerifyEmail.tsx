import { type ReactElement } from 'react';
import { router, usePage } from '@inertiajs/react';
import GuestLayout from '../../layouts/GuestLayout';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import type { SharedPageProps } from '../../types';

interface VerifyEmailLinks {
    verificationSend: string;
    logout: string;
}

interface VerifyEmailPageProps extends SharedPageProps {
    status: string | null;
    links: VerifyEmailLinks;
}

export default function VerifyEmail(): ReactElement {
    const { status, links } = usePage<VerifyEmailPageProps>().props;

    const handleResend = (): void => {
        router.post(links.verificationSend, {}, { preserveScroll: true });
    };

    const handleLogout = (): void => {
        router.post(links.logout);
    };

    return (
        <GuestLayout title="Verifică-ți adresa de email">
            <h1 className="font-sans font-bold text-2xl">Verifică-ți adresa de email</h1>
            <p className="mt-2 text-sm text-dim leading-relaxed">
                Ți-am trimis un email cu un link de confirmare. Apasă pe link ca să îți activezi contul. Dacă nu l-ai
                primit, îți trimitem altul.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mt-6 border border-hairline border-l-2 border-l-[#7dffa8] bg-[#06080a] px-4 py-3 text-sm font-medium text-em-green">
                    Ți-am trimis un nou link de confirmare pe adresa de email din cont.
                </div>
            )}

            <div className="mt-7 flex flex-col items-center justify-between gap-4 sm:flex-row">
                <PrimaryButton type="button" className="w-full sm:w-auto" onClick={handleResend}>
                    Retrimite emailul
                </PrimaryButton>

                <button
                    type="button"
                    onClick={handleLogout}
                    className="focus-ring rounded-sm text-sm text-dim transition-colors hover:text-beam"
                >
                    Deconectare
                </button>
            </div>
        </GuestLayout>
    );
}
