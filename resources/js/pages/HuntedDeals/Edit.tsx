import { useState, type FormEvent, type ReactElement } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import PhraseTagInput from '../../components/deals/PhraseTagInput';
import { ConfirmDialog } from '../../components/ui/ConfirmDialog';
import { DangerButton } from '../../components/ui/DangerButton';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { HuntedDeal, SharedPageProps } from '../../types';

interface HuntedDealsEditLinks {
    index: string;
    update: string;
    destroy: string;
}

interface HuntedDealsEditPageProps extends SharedPageProps {
    huntedDeal: HuntedDeal;
    links: HuntedDealsEditLinks;
}

interface HuntedDealFormData {
    search_term: string;
    is_active: boolean;
    notes: string;
    preferred_phrases: string[];
    excluded_phrases: string[];
}

const HELP_TEXT_CLASS = 'mt-2 max-w-[60ch] text-sm text-dim';

export default function HuntedDealsEdit(): ReactElement {
    const { huntedDeal, links } = usePage<HuntedDealsEditPageProps>().props;

    const { data, setData, put, processing, errors } = useForm<HuntedDealFormData>({
        search_term: huntedDeal.searchTerm,
        is_active: huntedDeal.isActive,
        notes: huntedDeal.notes ?? '',
        preferred_phrases: huntedDeal.preferredPhrases ?? [],
        excluded_phrases: huntedDeal.excludedPhrases ?? [],
    });

    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        put(links.update);
    };

    const handleDelete = (): void => {
        setDeleting(true);

        router.delete(links.destroy, {
            onFinish: () => {
                setDeleting(false);
                setConfirmingDelete(false);
            },
        });
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <p className="placard mb-1.5 text-[0.6rem]">Editează căutarea</p>
                <h2 className="break-words font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    {huntedDeal.searchTerm}
                </h2>
            </div>
            <div className="flex shrink-0 flex-wrap items-center gap-2.5">
                <Link href={links.index} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Toate căutările
                </Link>
                <Link href={huntedDeal.showUrl} className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Detalii
                </Link>
            </div>
        </div>
    );

    return (
        <AppLayout title="Editează căutarea" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-7 border border-hairline bg-bench px-5 py-6 sm:px-7 sm:py-7">
                        <div>
                            <InputLabel htmlFor="search_term" value="Termen căutat *" />
                            <TextInput
                                id="search_term"
                                type="text"
                                className="mt-2 block w-full"
                                value={data.search_term}
                                onChange={(event) => setData('search_term', event.target.value)}
                                required
                                autoFocus
                                placeholder="ex. iPhone 13, laptop gaming, apartament 2 camere"
                            />
                            <InputError message={errors.search_term} className="mt-2" />
                            <p className={HELP_TEXT_CLASS}>
                                Acesta este termenul pe care îl căutăm automat pe OLX România. Cu cât e mai specific, cu atât rezultatele sunt mai bune.
                            </p>
                        </div>

                        <div>
                            <div className="flex items-center gap-2.5">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(event) => setData('is_active', event.target.checked)}
                                    className="rounded-sm border-hairline bg-[#06080a] text-[#59e3ff] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30"
                                />
                                <label htmlFor="is_active" className="font-mono text-[0.7rem] uppercase text-[#eaf4f6]">
                                    Activă (pornește verificarea automată)
                                </label>
                            </div>
                            <InputError message={errors.is_active} className="mt-2" />
                            <p className={HELP_TEXT_CLASS}>
                                Când e activă, căutarea este inclusă în verificările automate de anunțuri.
                            </p>
                        </div>

                        <section className="space-y-6 border-y border-hairline py-6" aria-labelledby="phrase-tuning-heading">
                            <div>
                                <p className="placard text-[0.6rem]">Rafinare opțională</p>
                                <h3 id="phrase-tuning-heading" className="mt-1 font-sans text-base font-bold text-[#eaf4f6]">
                                    Semnale pentru rezultate mai bune
                                </h3>
                                <p className="mt-2 max-w-[60ch] text-sm text-dim">
                                    Adaugă expresii separate pentru a evidenția anunțurile potrivite sau pentru a evita variantele nedorite.
                                </p>
                            </div>

                            <PhraseTagInput
                                name="preferred_phrases"
                                label="Expresii preferate"
                                description="Un +5 mic la scor pentru fiecare expresie găsită în titlu; nu sunt obligatorii. Apasă Enter sau scrie o virgulă pentru a adăuga mai multe."
                                values={data.preferred_phrases}
                                errors={errors.preferred_phrases}
                                onChange={(tags) => setData('preferred_phrases', tags)}
                            />

                            <PhraseTagInput
                                name="excluded_phrases"
                                label="Expresii de exclus"
                                description="Evită anunțurile care conțin aceste expresii, de exemplu „pentru piese” sau „defect”."
                                placeholder="Scrie o expresie de exclus și apasă Enter"
                                values={data.excluded_phrases}
                                errors={errors.excluded_phrases}
                                tone="amber"
                                onChange={(tags) => setData('excluded_phrases', tags)}
                            />
                        </section>

                        <div>
                            <InputLabel htmlFor="notes" value="Notițe" />
                            <textarea
                                id="notes"
                                rows={4}
                                value={data.notes}
                                onChange={(event) => setData('notes', event.target.value)}
                                placeholder="Opțional: ce cauți exact, buget, cerințe specifice…"
                                className="mt-2 block w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30"
                            />
                            <InputError message={errors.notes} className="mt-2" />
                            <p className={HELP_TEXT_CLASS}>
                                Doar pentru referința ta — nu influențează căutarea.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-hairline pt-6">
                            <DangerButton type="button" onClick={() => setConfirmingDelete(true)}>
                                Șterge căutarea
                            </DangerButton>
                            <div className="flex items-center gap-2.5">
                                <Link href={huntedDeal.showUrl} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                                    Anulează
                                </Link>
                                <PrimaryButton processing={processing} className="text-[0.65rem]">
                                    Salvează modificările
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>

                    <div className="mt-6 border border-hairline bg-bench px-5 py-5">
                        <h3 className="placard mb-4 text-[0.65rem]">Informații căutare</h3>
                        <dl className="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                            <div>
                                <dt className="placard text-[0.58rem]">Creată</dt>
                                <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.createdAt}</dd>
                            </div>
                            <div>
                                <dt className="placard text-[0.58rem]">Actualizată</dt>
                                <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.updatedAt}</dd>
                            </div>
                            <div>
                                <dt className="placard text-[0.58rem]">Ultima verificare</dt>
                                <dd
                                    className={`mt-1.5 font-mono text-[0.8rem] tabular-nums ${
                                        huntedDeal.lastCrawledAt ? 'text-[#eaf4f6]' : 'text-em-amber'
                                    }`}
                                >
                                    {huntedDeal.lastCrawledAt ?? 'Neverificată'}
                                </dd>
                            </div>
                            <div>
                                <dt className="placard text-[0.58rem]">Anunțuri găsite</dt>
                                <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.dealsCount}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                show={confirmingDelete}
                onClose={() => setConfirmingDelete(false)}
                onConfirm={handleDelete}
                kicker="Zonă ireversibilă"
                title="Șterge căutarea"
                description={
                    <>
                        Sigur vrei să ștergi &bdquo;{huntedDeal.searchTerm}&rdquo;? Se șterg și toate anunțurile și instantaneele asociate. Acțiunea nu poate fi anulată.
                    </>
                }
                cancelLabel="Anulează"
                confirmLabel="Șterge definitiv"
                processing={deleting}
            />
        </AppLayout>
    );
}
