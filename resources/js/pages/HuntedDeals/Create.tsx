import { type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import PhraseTagInput from '../../components/deals/PhraseTagInput';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import type { SharedPageProps } from '../../types';

interface HuntedDealsCreateLinks {
    store: string;
    index: string;
}

interface HuntedDealsCreatePageProps extends SharedPageProps {
    links: HuntedDealsCreateLinks;
}

interface HuntedDealFormData {
    search_term: string;
    is_active: boolean;
    notes: string;
    preferred_phrases: string[];
    excluded_phrases: string[];
}

const TIPS = [
    'Folosește termeni specifici, precum „iPhone 13 Pro", nu doar „telefon".',
    'Include marca și modelul atunci când le știi.',
    'Folosește termenii din piața românească (de ex. „apartament", nu „apartment").',
    'Verificarea automată caută anunțuri noi o dată pe oră.',
];

const HELP_TEXT_CLASS = 'mt-2 max-w-[60ch] text-sm text-dim';

export default function HuntedDealsCreate(): ReactElement {
    const { links } = usePage<HuntedDealsCreatePageProps>().props;

    const { data, setData, post, processing, errors } = useForm<HuntedDealFormData>({
        search_term: '',
        is_active: true,
        notes: '',
        preferred_phrases: [],
        excluded_phrases: [],
    });

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        post(links.store);
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Căutare nouă</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    Adaugă o căutare urmărită
                </h2>
            </div>
            <Link href={links.index} className="beamkey focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]">
                &larr; Toate căutările
            </Link>
        </div>
    );

    return (
        <AppLayout title="Căutare nouă" header={header}>
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

                        <div className="flex items-center justify-end gap-2.5 border-t border-hairline pt-6">
                            <Link href={links.index} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                                Anulează
                            </Link>
                            <PrimaryButton processing={processing} className="text-[0.65rem]">
                                + Creează căutarea
                            </PrimaryButton>
                        </div>
                    </form>

                    <div className="mt-6 border border-hairline bg-bench px-5 py-5">
                        <h3 className="placard mb-4 text-[0.65rem]">Sfaturi pentru rezultate mai bune</h3>
                        <ul className="space-y-3">
                            {TIPS.map((tip) => (
                                <li key={tip} className="flex items-start gap-3">
                                    <span
                                        className="mt-[0.4rem] inline-block h-1 w-1 shrink-0 rounded-full bg-[#59e3ff]"
                                        style={{ boxShadow: '0 0 6px rgba(89,227,255,0.6)' }}
                                        aria-hidden="true"
                                    />
                                    <span className="text-sm text-dim">{tip}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
