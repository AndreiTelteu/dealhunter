import { useMemo, useState, useId } from 'react';
import type { KeyboardEvent, ReactElement } from 'react';
import { InputError } from '../ui/InputError';

type PhraseTone = 'beam' | 'amber';

interface PhraseTagInputProps {
    /** Form field name — hidden inputs submit `${name}[]` like the Blade one. */
    name: string;
    label: string;
    description: string;
    placeholder?: string;
    /** Initial tags (controller prop or old input) — uncontrolled, like Alpine. */
    values?: string[];
    tone?: PhraseTone;
    /** Server validation messages for this field (e.g. from `useForm().errors`). */
    errors?: string | string[];
}

const ACCENT_CLASSES: Record<PhraseTone, string> = {
    amber:
        'border-[#ffc46b]/35 bg-[#ffc46b]/[0.08] text-[#ffe0a8] hover:border-[#ffc46b]/65 hover:text-[#fff0d2] focus-visible:outline-[#ffc46b]',
    beam: 'border-[#59e3ff]/35 bg-[#59e3ff]/[0.08] text-[#b7f4ff] hover:border-[#59e3ff]/65 hover:text-[#eafcff] focus-visible:outline-[#59e3ff]',
};

function normalizeInitial(values: string[] | undefined): string[] {
    if (!values) {
        return [];
    }

    return values.filter((value) => value.trim() !== '');
}

/**
 * React port of components/phrase-tag-input.blade.php — tag editor for
 * preferred/excluded phrases. Same behavior as the Alpine original:
 * Enter, comma or blur commits the draft (comma-split), case-insensitive
 * dedupe, backspace on an empty draft removes the last tag, and every tag
 * submits as a `${name}[]` hidden input inside the enclosing form.
 */
export default function PhraseTagInput({
    name,
    label,
    description,
    placeholder = 'Scrie o expresie și apasă Enter',
    values,
    tone = 'beam',
    errors,
}: PhraseTagInputProps): ReactElement {
    const initialTags = useMemo(() => normalizeInitial(values), [values]);
    const [tags, setTags] = useState<string[]>(initialTags);
    const [draft, setDraft] = useState('');
    const inputId = `${name}_input`;
    const hintId = `${name}_hint`;
    const errorId = useId();

    const addDraft = (): void => {
        if (draft.trim() === '') {
            return;
        }

        setTags((current) => {
            const phrases = draft
                .split(',')
                .map((phrase) => phrase.trim())
                .filter((phrase) => phrase !== '');

            const merged = [...current];

            phrases.forEach((phrase) => {
                const exists = merged.some((tag) => tag.toLocaleLowerCase() === phrase.toLocaleLowerCase());

                if (!exists) {
                    merged.push(phrase);
                }
            });

            return merged;
        });
        setDraft('');
    };

    const removeTag = (index: number): void => {
        setTags((current) => current.filter((_, i) => i !== index));
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>): void => {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addDraft();

            return;
        }

        if (event.key === 'Backspace' && draft === '' && tags.length > 0) {
            removeTag(tags.length - 1);
        }
    };

    const hasErrors = Boolean(errors && (Array.isArray(errors) ? errors.length > 0 : true));

    return (
        <div>
            <label htmlFor={inputId} className="placard text-[0.6rem]">
                {label}
            </label>

            <div className="mt-2 border border-hairline bg-[#06080a] p-2.5 transition-colors focus-within:border-[#59e3ff]/60 focus-within:ring-2 focus-within:ring-[#59e3ff]/20">
                <div className="flex flex-wrap items-center gap-2" aria-live="polite">
                    {tags.map((tag, index) => (
                        <span
                            key={`${tag}-${index}`}
                            className={`inline-flex max-w-full items-center gap-1.5 border px-2 py-1 font-mono text-[0.68rem] leading-5 ${ACCENT_CLASSES[tone]}`}
                        >
                            <span className="min-w-0 break-words">{tag}</span>
                            <button
                                type="button"
                                onClick={() => removeTag(index)}
                                aria-label={`Elimină expresia ${tag}`}
                                className="focus-ring -mr-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-sm text-current/70 transition-colors hover:bg-white/10 hover:text-current"
                            >
                                <span aria-hidden="true">×</span>
                            </button>
                            <input type="hidden" name={`${name}[]`} value={tag} />
                        </span>
                    ))}

                    <input
                        id={inputId}
                        type="text"
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                        onKeyDown={handleKeyDown}
                        onBlur={addDraft}
                        placeholder={placeholder}
                        aria-describedby={hasErrors ? `${errorId} ${hintId}` : hintId}
                        aria-invalid={hasErrors || undefined}
                        className="min-w-40 flex-1 border-0 bg-transparent px-1 py-1 font-mono text-sm text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 focus:ring-0"
                    />
                </div>
            </div>

            <InputError message={errors} className="mt-2" />
            <p id={hintId} className="mt-2 max-w-[60ch] text-sm text-dim">
                {description}
            </p>
        </div>
    );
}
