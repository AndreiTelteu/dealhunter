import { useRef, useState, type FormEvent, type ReactElement } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import { InputError } from '../../components/ui/InputError';
import { InputLabel } from '../../components/ui/InputLabel';
import { PrimaryButton } from '../../components/ui/PrimaryButton';
import { TextInput } from '../../components/ui/TextInput';
import { csrfToken } from '../../lib/csrf';
import type {
    AiClassificationConfig,
    AiClassificationResult,
    SharedPageProps,
} from '../../types';

interface AiClassificationLinks {
    test: string;
    testConnection: string;
}

interface AiClassificationPageProps extends SharedPageProps, AiClassificationConfig {
    links: AiClassificationLinks;
}

type SpectrumState = 'green' | 'amber' | 'red';

const STATE_COLORS: Record<SpectrumState, string> = {
    green: '#7dffa8',
    amber: '#ffc46b',
    red: '#ff5d5d',
};

const STATE_TEXT_CLASS: Record<SpectrumState | 'dim', string> = {
    green: 'text-em-green',
    amber: 'text-em-amber',
    red: 'text-em-red',
    dim: 'text-dim',
};

interface Reading {
    label: string;
    value: string;
    state: SpectrumState;
    height: number;
}

interface Readout {
    label: string;
    value: string;
    state: SpectrumState | 'dim';
}

interface FieldErrors {
    [field: string]: string[];
}

function percentage(value: unknown): string {
    return `${(Number(value || 0) * 100).toFixed(1)}%`;
}

function workingLabel(value: boolean | null): string {
    if (value === true) {
        return 'Pare funcțional';
    }
    if (value === false) {
        return 'Pare defect';
    }

    return 'Nesigur';
}

function workingState(value: boolean | null): SpectrumState {
    if (value === true) {
        return 'green';
    }
    if (value === false) {
        return 'red';
    }

    return 'amber';
}

function confidenceState(value: number): SpectrumState {
    if (value >= 0.7) {
        return 'green';
    }
    if (value > 0) {
        return 'amber';
    }

    return 'red';
}

function confidenceHeight(value: unknown): number {
    return Math.max(18, Math.round(Number(value || 0) * 100));
}

function conditionHeight(value: boolean | null): number {
    if (value === true) {
        return 78;
    }
    if (value === false) {
        return 42;
    }

    return 56;
}

function ReadoutRow({ label, value, state, text = false }: Readout & { text?: boolean }): ReactElement {
    return (
        <div>
            <dt className="placard text-[0.55rem]">{label}</dt>
            <dd className={`mt-2 break-words ${text ? 'text-sm leading-relaxed text-dim' : `font-mono text-sm tabular-nums ${STATE_TEXT_CLASS[state]}`}`}>
                {value}
            </dd>
        </div>
    );
}

function buildReadings(result: AiClassificationResult): Reading[] {
    const ai = result.ai_result;
    if (!ai) {
        return [];
    }

    return [
        {
            label: 'Scor potrivire',
            value: `${ai.intent_score}%`,
            state: ai.matches_intent ? 'green' : 'red',
            height: Math.max(10, Math.round(ai.intent_score * 0.84)),
        },
        {
            label: 'Stare',
            value: workingLabel(ai.likely_working),
            state: workingState(ai.likely_working),
            height: conditionHeight(ai.likely_working),
        },
        {
            label: 'Încredere',
            value: percentage(ai.confidence),
            state: confidenceState(ai.confidence),
            height: confidenceHeight(ai.confidence),
        },
    ];
}

function buildAiReadouts(result: AiClassificationResult): Readout[] {
    const ai = result.ai_result;
    if (!ai) {
        return [];
    }

    return [
        { label: 'Scor potrivire', value: `${ai.intent_score}%`, state: ai.matches_intent ? 'green' : 'red' },
        { label: 'Produs căutat', value: ai.matches_intent ? 'Da' : 'Nu', state: ai.matches_intent ? 'green' : 'red' },
        { label: 'Stare', value: workingLabel(ai.likely_working), state: workingState(ai.likely_working) },
        { label: 'Încredere', value: percentage(ai.confidence), state: confidenceState(ai.confidence) },
        { label: 'Motivare', value: ai.reasoning, state: 'dim' },
    ];
}

function buildKeywordReadouts(result: AiClassificationResult): Readout[] {
    const keyword = result.keyword_result;
    if (!keyword) {
        return [];
    }

    return [
        {
            label: 'Scor potrivire',
            value: keyword.intent_score !== null ? `${keyword.intent_score}%` : '-',
            state: keyword.matches_intent ? 'green' : 'red',
        },
        { label: 'Produs căutat', value: keyword.matches_intent ? 'Da' : 'Nu', state: keyword.matches_intent ? 'green' : 'red' },
        { label: 'Stare', value: workingLabel(keyword.likely_working), state: workingState(keyword.likely_working) },
        { label: 'Încredere', value: percentage(keyword.confidence), state: confidenceState(keyword.confidence) },
        { label: 'Motivare', value: keyword.reasoning, state: 'dim' },
    ];
}

function buildComparisonReadouts(result: AiClassificationResult): Readout[] {
    const comparison = result.comparison;
    if (!comparison) {
        return [];
    }

    return [
        { label: 'Potrivire', value: comparison.intent_match ? 'Acord' : 'Diferă', state: comparison.intent_match ? 'green' : 'amber' },
        { label: 'Stare', value: comparison.working_condition_match ? 'Acord' : 'Diferă', state: comparison.working_condition_match ? 'green' : 'amber' },
    ];
}

const DEFAULT_DESCRIPTION =
    'Laptop functional, stare foarte buna, fara probleme. Procesor Intel i5, 8GB RAM, SSD 256GB. Testat si merge perfect.';

export default function AiClassificationIndex(): ReactElement {
    const { availableModels, connectionTest, currentProvider, currentModel, aiEnabled, links } =
        usePage<AiClassificationPageProps>().props;

    const [searchTerm, setSearchTerm] = useState('laptop');
    const [title, setTitle] = useState('Laptop Dell Latitude E7450');
    const [description, setDescription] = useState(DEFAULT_DESCRIPTION);

    const [analyzing, setAnalyzing] = useState(false);
    const [analysisStatus, setAnalysisStatus] = useState('');
    const [result, setResult] = useState<AiClassificationResult | null>(null);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [error, setError] = useState<string | null>(null);

    const [checkingConnection, setCheckingConnection] = useState(false);
    const [connectionStatus, setConnectionStatus] = useState('');

    const resultsRef = useRef<HTMLElement>(null);
    const errorRef = useRef<HTMLElement>(null);

    const header = (
        <div>
            <p className="placard mb-1.5 text-[0.6rem]">Analiză anunț</p>
            <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                Verifică potrivirea unui anunț
            </h2>
        </div>
    );

    const clearFieldErrors = (): void => {
        setFieldErrors({});
    };

    const handleSubmit = async (event: FormEvent<HTMLFormElement>): Promise<void> => {
        event.preventDefault();
        clearFieldErrors();
        setResult(null);
        setError(null);

        const body = new FormData();
        body.append('search_term', searchTerm);
        body.append('title', title);
        body.append('description', description);

        setAnalyzing(true);
        setAnalysisStatus('Analiza este în curs.');

        try {
            const response = await fetch(links.test, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body,
            });
            const payload = (await response.json()) as AiClassificationResult & { errors?: FieldErrors };

            if (payload.success) {
                setResult(payload);
                setAnalysisStatus('Analiza este gata.');
                resultsRef.current?.focus();
            } else if (payload.errors) {
                setFieldErrors(payload.errors);
                setAnalysisStatus('Completează câmpurile marcate.');
            } else {
                setError(payload.error ?? 'Analiza nu a putut fi finalizată.');
                setAnalysisStatus('Analiza a eșuat.');
                errorRef.current?.focus();
            }
        } catch (networkError) {
            setError(`Cererea a eșuat: ${networkError instanceof Error ? networkError.message : 'eroare necunoscută'}`);
            setAnalysisStatus('Analiza a eșuat.');
            errorRef.current?.focus();
        } finally {
            setAnalyzing(false);
        }
    };

    const handleTestConnection = async (): Promise<void> => {
        setCheckingConnection(true);
        setConnectionStatus('Se verifică conexiunea.');

        try {
            const response = await fetch(links.testConnection, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            const payload = (await response.json()) as { success: boolean; error?: string };

            if (payload.success) {
                setConnectionStatus('Conexiunea este disponibilă. Pagina se reîncarcă.');
                router.reload();
                return;
            }

            setConnectionStatus(`Conexiunea a eșuat: ${payload.error ?? 'eroare necunoscută.'}`);
        } catch (networkError) {
            setConnectionStatus(`Cererea a eșuat: ${networkError instanceof Error ? networkError.message : 'eroare necunoscută'}`);
        } finally {
            setCheckingConnection(false);
        }
    };

    const readings = result ? buildReadings(result) : [];
    const aiReadouts = result ? buildAiReadouts(result) : [];
    const keywordReadouts = result ? buildKeywordReadouts(result) : [];
    const comparisonReadouts = result ? buildComparisonReadouts(result) : [];

    return (
        <AppLayout title="Analiză anunț" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section aria-labelledby="analysis-heading">
                        <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start">
                            <div>
                                <h1 id="analysis-heading" className="font-sans text-2xl font-bold text-[#eaf4f6] sm:text-3xl">
                                    Testează clasificarea înainte de urmărire
                                </h1>
                                <p className="mt-2 text-sm leading-relaxed text-dim sm:text-base" style={{ maxWidth: '62ch' }}>
                                    Introdu căutarea și un anunț. Vezi potrivirea, starea probabilă și explicația primită.
                                </p>
                            </div>

                            <aside className="border-y border-hairline py-4 xl:border-y-0 xl:border-l xl:pl-6" aria-labelledby="provider-heading">
                                <p id="provider-heading" className="placard text-[0.6rem]">Sursă analiză</p>
                                <dl className="mt-4 space-y-4">
                                    <div>
                                        <dt className="placard text-[0.55rem]">Furnizor</dt>
                                        <dd className="mt-1 break-words font-mono text-xs tabular-nums text-[#eaf4f6]">{currentProvider}</dd>
                                    </div>
                                    <div>
                                        <dt className="placard text-[0.55rem]">Model</dt>
                                        <dd className="mt-1 break-words font-mono text-xs tabular-nums text-[#eaf4f6]">{currentModel}</dd>
                                    </div>
                                    <div>
                                        <dt className="placard text-[0.55rem]">Stare</dt>
                                        <dd className="mt-1">
                                            {aiEnabled && connectionTest.success ? (
                                                <span className="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-green">
                                                    <span
                                                        className="h-1.5 w-1.5 rounded-full bg-[#7dffa8]"
                                                        style={{ boxShadow: '0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(125,255,168,0.7)' }}
                                                        aria-hidden="true"
                                                    />
                                                    Conectat
                                                </span>
                                            ) : aiEnabled ? (
                                                <span className="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-red">
                                                    <span
                                                        className="h-1.5 w-1.5 rounded-full bg-[#ff5d5d]"
                                                        style={{ boxShadow: '0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(255,93,93,0.7)' }}
                                                        aria-hidden="true"
                                                    />
                                                    Conexiune eșuată
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-amber">
                                                    <span
                                                        className="h-1.5 w-1.5 rounded-full bg-[#ffc46b]"
                                                        style={{ boxShadow: '0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(255,196,107,0.7)' }}
                                                        aria-hidden="true"
                                                    />
                                                    AI oprit
                                                </span>
                                            )}
                                        </dd>
                                        {!connectionTest.success && (
                                            <p className="mt-2 break-words text-xs leading-relaxed text-em-red">
                                                {connectionTest.error ?? 'Conexiunea nu a putut fi verificată.'}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <dt className="placard text-[0.55rem]">Fallback</dt>
                                        <dd className="mt-1 text-xs leading-relaxed text-em-amber">Cuvinte-cheie dacă analiza nu răspunde.</dd>
                                    </div>
                                    {availableModels.length > 0 && (
                                        <div>
                                            <dt className="placard text-[0.55rem]">Modele disponibile</dt>
                                            <dd className="mt-1 break-words font-mono text-[0.65rem] leading-relaxed text-dim">{availableModels.join(' · ')}</dd>
                                        </div>
                                    )}
                                </dl>
                                <button
                                    id="test-connection-btn"
                                    type="button"
                                    onClick={handleTestConnection}
                                    disabled={checkingConnection}
                                    className="beamkey focus-ring mt-5 w-full rounded-sm px-3 py-2.5 text-[0.6rem] disabled:pointer-events-none disabled:opacity-40"
                                    aria-describedby="connection-status"
                                >
                                    {checkingConnection ? 'Se verifică…' : 'Verifică conexiunea'}
                                </button>
                                <p id="connection-status" className="sr-only" aria-live="polite">
                                    {connectionStatus}
                                </p>
                            </aside>
                        </div>
                    </section>

                    <section className="border border-hairline bg-bench" aria-labelledby="form-heading">
                        <div className="border-b border-hairline px-5 py-4 sm:px-6">
                            <p className="placard text-[0.6rem]">Intrare</p>
                            <h2 id="form-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Datele anunțului</h2>
                        </div>

                        <form id="classification-form" className="p-5 sm:p-6" onSubmit={handleSubmit}>
                            <div className="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="search_term" value="Căutare" />
                                    <TextInput
                                        id="search_term"
                                        name="search_term"
                                        type="text"
                                        value={searchTerm}
                                        onChange={(event) => setSearchTerm(event.target.value)}
                                        required
                                        maxLength={255}
                                        autoComplete="off"
                                        className="mt-2 block w-full"
                                        aria-describedby="search-term-error"
                                    />
                                    <InputError message={fieldErrors.search_term} className="mt-1.5" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="title" value="Titlu anunț" />
                                    <TextInput
                                        id="title"
                                        name="title"
                                        type="text"
                                        value={title}
                                        onChange={(event) => setTitle(event.target.value)}
                                        required
                                        maxLength={500}
                                        autoComplete="off"
                                        className="mt-2 block w-full"
                                        aria-describedby="title-error"
                                    />
                                    <InputError message={fieldErrors.title} className="mt-1.5" />
                                </div>
                                <div className="lg:col-span-2">
                                    <InputLabel htmlFor="description" value="Descriere" />
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows={5}
                                        maxLength={2000}
                                        value={description}
                                        onChange={(event) => setDescription(event.target.value)}
                                        placeholder="Descrie starea și detaliile relevante..."
                                        className="mt-2 block w-full resize-y rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm leading-relaxed text-[#eaf4f6] shadow-none placeholder:text-[#8fa8b0]/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30"
                                        aria-describedby="description-error"
                                    />
                                    <InputError message={fieldErrors.description} className="mt-1.5" />
                                </div>
                            </div>
                            <div className="mt-6 flex flex-wrap items-center gap-4 border-t border-hairline pt-5">
                                <PrimaryButton type="submit" processing={analyzing} className="px-5 py-3 text-[0.65rem]" aria-describedby="analysis-status">
                                    {analyzing ? 'Se analizează…' : 'Analizează anunțul'}
                                </PrimaryButton>
                                <p id="analysis-status" className="font-mono text-xs tabular-nums text-dim" aria-live="polite">
                                    {analysisStatus}
                                </p>
                            </div>
                        </form>
                    </section>

                    {result && result.success && (
                        <section
                            id="results"
                            ref={resultsRef}
                            className="border border-hairline"
                            aria-labelledby="results-heading"
                            tabIndex={-1}
                        >
                            <div className="flex flex-wrap items-end justify-between gap-4 border-b border-hairline bg-bench px-5 py-4 sm:px-6">
                                <div>
                                    <p className="placard text-[0.6rem]">Rezultat</p>
                                    <h2 id="results-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Citire clasificare</h2>
                                </div>
                                <p id="result-provider" className="font-mono text-[0.65rem] tabular-nums text-dim">Rezultat primit</p>
                            </div>

                            <div className="graticule relative overflow-hidden px-5 py-8 sm:px-6 sm:py-10">
                                <div className="beam-core beam-travel absolute bottom-0 top-0 w-[3px]" aria-hidden="true" />
                                <div
                                    className="relative grid min-h-44 grid-cols-4 items-end gap-4 border-b border-hairline pb-7 sm:gap-8"
                                    role="img"
                                    aria-label={`Citire spectrală a clasificării: ${readings.map((reading) => `${reading.label}: ${reading.value}`).join('. ')}.`}
                                >
                                    {readings.map((reading, index) => (
                                        <div key={reading.label} className="flex min-w-0 flex-col items-center gap-3">
                                            <span
                                                className="spec-line line-ignite w-[3px]"
                                                style={{
                                                    height: `${reading.height}px`,
                                                    background: STATE_COLORS[reading.state],
                                                    color: STATE_COLORS[reading.state],
                                                    animationDelay: `${0.8 + index * 0.14}s`,
                                                }}
                                                aria-hidden="true"
                                            />
                                            <span className="placard text-center text-[0.52rem]">{reading.label}</span>
                                        </div>
                                    ))}
                                </div>
                                <dl className="relative mt-7 grid gap-x-6 gap-y-6 sm:grid-cols-2 xl:grid-cols-4">
                                    {aiReadouts.map((readout) => (
                                        <ReadoutRow key={readout.label} label={readout.label} value={readout.value} state={readout.state} text={readout.state === 'dim'} />
                                    ))}
                                </dl>
                            </div>

                            <div className="border-t border-hairline bg-bench px-5 py-6 sm:px-6">
                                <div className="grid gap-6 lg:grid-cols-2">
                                    <div>
                                        <p className="placard text-[0.6rem]">Citire clasificator</p>
                                        <dl className="mt-4 grid gap-4 border-t border-hairline pt-4 sm:grid-cols-2">
                                            {keywordReadouts.map((readout) => (
                                                <ReadoutRow key={readout.label} label={readout.label} value={readout.value} state={readout.state} text={readout.state === 'dim'} />
                                            ))}
                                        </dl>
                                    </div>
                                    <div className="lg:border-l lg:border-hairline lg:pl-6">
                                        <p className="placard text-[0.6rem]">Acord</p>
                                        <dl className="mt-4 grid grid-cols-2 gap-4 border-t border-hairline pt-4">
                                            {comparisonReadouts.map((readout) => (
                                                <ReadoutRow key={readout.label} label={readout.label} value={readout.value} state={readout.state} />
                                            ))}
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </section>
                    )}

                    {error && (
                        <section
                            id="error-display"
                            ref={errorRef}
                            className="border border-[#ff5d5d]/50 bg-bench px-5 py-5"
                            aria-labelledby="error-heading"
                            role="alert"
                            tabIndex={-1}
                        >
                            <p className="placard text-[0.6rem] text-em-red">Eroare</p>
                            <h2 id="error-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Analiza nu a reușit</h2>
                            <p id="error-message" className="mt-2 break-words text-sm leading-relaxed text-em-red">{error}</p>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
