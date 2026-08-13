import type { ReactElement, ReactNode } from 'react';
import { DangerButton } from './DangerButton';
import { Modal, type ModalMaxWidth } from './Modal';
import { SecondaryButton } from './SecondaryButton';

interface ConfirmDialogProps {
    show: boolean;
    onClose: () => void;
    /** Called on confirm. Omit it to let an enclosing form handle the submit. */
    onConfirm?: () => void;
    /** Placard kicker above the heading (e.g. "Confirmare", "Zonă ireversibilă"). */
    kicker?: string;
    title: string;
    description?: ReactNode;
    /** Cancel label; defaults to "Renunță". */
    cancelLabel?: string;
    /** Confirm label; defaults to "Confirmă". */
    confirmLabel?: string;
    /** Mid-submit state (e.g. Inertia useForm `processing`). */
    processing?: boolean;
    maxWidth?: ModalMaxWidth;
    /** Extra content between the description and the actions (e.g. a password field). */
    children?: ReactNode;
}

/**
 * Confirmation dialog built on Modal + danger/secondary beam-key buttons,
 * matching the delete-user-form pattern: kicker placard, heading,
 * description, optional extra fields, cancel/confirm action row.
 */
export function ConfirmDialog({
    show,
    onClose,
    onConfirm,
    kicker = 'Confirmare',
    title,
    description,
    cancelLabel = 'Renunță',
    confirmLabel = 'Confirmă',
    processing = false,
    maxWidth = '2xl',
    children,
}: ConfirmDialogProps): ReactElement | null {
    return (
        <Modal show={show} onClose={onClose} maxWidth={maxWidth} ariaLabel={title}>
            <div className="p-5 sm:p-7">
                <p className="font-mono text-[0.6rem] uppercase text-em-red">{kicker}</p>
                <h2 className="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">{title}</h2>

                {description && <p className="mt-1.5 max-w-xl text-sm text-dim">{description}</p>}

                {children}

                <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <SecondaryButton onClick={onClose} disabled={processing}>
                        {cancelLabel}
                    </SecondaryButton>

                    <DangerButton
                        type={onConfirm ? 'button' : 'submit'}
                        onClick={onConfirm}
                        processing={processing}
                    >
                        {confirmLabel}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
