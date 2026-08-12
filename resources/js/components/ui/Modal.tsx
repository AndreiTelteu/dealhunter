import { useCallback, useEffect, useRef, type KeyboardEvent as ReactKeyboardEvent, type ReactElement, type ReactNode } from 'react';

export type ModalMaxWidth = 'sm' | 'md' | 'lg' | 'xl' | '2xl';

const MAX_WIDTHS: Record<ModalMaxWidth, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
};

interface ModalProps {
    show: boolean;
    /** Called when the user requests closing (backdrop click, Escape). */
    onClose?: () => void;
    maxWidth?: ModalMaxWidth;
    /** Accessible name for the dialog. */
    ariaLabel?: string;
    children: ReactNode;
}

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled]), details, [tabindex]:not([tabindex="-1"])';

/**
 * Accessible React port of x-modal: fixed overlay with the chamber-dark
 * backdrop, focus trap (Tab / Shift+Tab), Escape to close, backdrop click
 * to close, body scroll lock, and `role="dialog"` semantics.
 */
export function Modal({
    show,
    onClose,
    maxWidth = '2xl',
    ariaLabel,
    children,
}: ModalProps): ReactElement | null {
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!show) {
            return;
        }

        document.body.classList.add('overflow-y-hidden');

        const timer = window.setTimeout(() => {
            const focusables = panelRef.current?.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR);

            (focusables?.[0] ?? panelRef.current)?.focus();
        }, 100);

        return () => {
            document.body.classList.remove('overflow-y-hidden');
            window.clearTimeout(timer);
        };
    }, [show]);

    const moveFocus = useCallback((event: KeyboardEvent): void => {
        const focusables = Array.from(
            panelRef.current?.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR) ?? [],
        );

        if (focusables.length === 0) {
            event.preventDefault();

            return;
        }

        const index = focusables.indexOf(document.activeElement as HTMLElement);

        if (event.shiftKey) {
            focusables[Math.max(0, index - 1) || focusables.length - 1].focus();
        } else {
            focusables[(index + 1) % focusables.length].focus();
        }

        event.preventDefault();
    }, []);

    const handleKeyDown = useCallback(
        (event: ReactKeyboardEvent<HTMLDivElement>): void => {
            if (event.key === 'Escape') {
                event.stopPropagation();
                onClose?.();

                return;
            }

            if (event.key === 'Tab') {
                moveFocus(event.nativeEvent);
            }
        },
        [moveFocus, onClose],
    );

    if (!show) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            onKeyDown={handleKeyDown}
        >
            <div
                className="fixed inset-0 bg-[#04070a] opacity-85 transition-opacity"
                onClick={() => onClose?.()}
                aria-hidden="true"
            ></div>

            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-label={ariaLabel}
                tabIndex={-1}
                className={`relative mb-6 bg-bench border border-hairline rounded-sm overflow-hidden transform transition-all sm:w-full ${MAX_WIDTHS[maxWidth]} sm:mx-auto`}
                style={{ boxShadow: '0 24px 48px -16px rgba(0,0,0,0.9)' }}
            >
                {children}
            </div>
        </div>
    );
}
