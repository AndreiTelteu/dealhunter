<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="placard mb-1.5 text-[0.6rem]">Analiză anunț</p>
            <h2 class="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                Verifică potrivirea unui anunț
            </h2>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="analysis-heading">
                <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start">
                    <div>
                        <h1 id="analysis-heading" class="font-sans text-2xl font-bold text-[#eaf4f6] sm:text-3xl">
                            Testează clasificarea înainte de urmărire
                        </h1>
                        <p class="mt-2 text-sm leading-relaxed text-dim sm:text-base" style="max-width:62ch">
                            Introdu căutarea și un anunț. Vezi potrivirea, starea probabilă și explicația primită.
                        </p>
                    </div>

                    <aside class="border-y border-hairline py-4 xl:border-y-0 xl:border-l xl:pl-6" aria-labelledby="provider-heading">
                        <p id="provider-heading" class="placard text-[0.6rem]">Sursă analiză</p>
                        <dl class="mt-4 space-y-4">
                            <div>
                                <dt class="placard text-[0.55rem]">Furnizor</dt>
                                <dd class="mt-1 break-words font-mono text-xs tabular-nums text-[#eaf4f6]">{{ $current_provider }}</dd>
                            </div>
                            <div>
                                <dt class="placard text-[0.55rem]">Model</dt>
                                <dd class="mt-1 break-words font-mono text-xs tabular-nums text-[#eaf4f6]">{{ $current_model }}</dd>
                            </div>
                            <div>
                                <dt class="placard text-[0.55rem]">Stare</dt>
                                <dd class="mt-1">
                                    @if($ai_enabled && $connection_test['success'])
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-green">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#7dffa8]" style="box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(125,255,168,0.7);" aria-hidden="true"></span>
                                            Conectat
                                        </span>
                                    @elseif($ai_enabled)
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-red">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#ff5d5d]" style="box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(255,93,93,0.7);" aria-hidden="true"></span>
                                            Conexiune eșuată
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 font-mono text-[0.65rem] uppercase text-em-amber">
                                            <span class="h-1.5 w-1.5 rounded-full bg-[#ffc46b]" style="box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 7px rgba(255,196,107,0.7);" aria-hidden="true"></span>
                                            AI oprit
                                        </span>
                                    @endif
                                </dd>
                                @if(!$connection_test['success'])
                                    <p class="mt-2 break-words text-xs leading-relaxed text-em-red">{{ $connection_test['error'] ?? 'Conexiunea nu a putut fi verificată.' }}</p>
                                @endif
                            </div>
                            <div>
                                <dt class="placard text-[0.55rem]">Fallback</dt>
                                <dd class="mt-1 text-xs leading-relaxed text-em-amber">Cuvinte-cheie dacă analiza nu răspunde.</dd>
                            </div>
                        </dl>
                        <button id="test-connection-btn" type="button" class="beamkey focus-ring mt-5 w-full rounded-sm px-3 py-2.5 text-[0.6rem]" aria-describedby="connection-status">
                            Verifică conexiunea
                        </button>
                        <p id="connection-status" class="sr-only" aria-live="polite"></p>
                    </aside>
                </div>
            </section>

            <section class="border border-hairline bg-bench" aria-labelledby="form-heading">
                <div class="border-b border-hairline px-5 py-4 sm:px-6">
                    <p class="placard text-[0.6rem]">Intrare</p>
                    <h2 id="form-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Datele anunțului</h2>
                </div>

                <form id="classification-form" class="p-5 sm:p-6" novalidate>
                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <label for="search_term" class="placard text-[0.6rem]">Căutare</label>
                            <input id="search_term" name="search_term" type="text" value="laptop" required maxlength="255" autocomplete="off"
                                class="mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-[#8fa8b0]/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30" aria-describedby="search-term-error">
                            <p id="search-term-error" class="mt-1.5 hidden text-xs text-em-red"></p>
                        </div>
                        <div>
                            <label for="title" class="placard text-[0.6rem]">Titlu anunț</label>
                            <input id="title" name="title" type="text" value="Laptop Dell Latitude E7450" required maxlength="500" autocomplete="off"
                                class="mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-[#8fa8b0]/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30" aria-describedby="title-error">
                            <p id="title-error" class="mt-1.5 hidden text-xs text-em-red"></p>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="description" class="placard text-[0.6rem]">Descriere</label>
                            <textarea id="description" name="description" rows="5" maxlength="2000" placeholder="Descrie starea și detaliile relevante..."
                                class="mt-2 block w-full resize-y rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm leading-relaxed text-[#eaf4f6] shadow-none placeholder:text-[#8fa8b0]/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">Laptop functional, stare foarte buna, fara probleme. Procesor Intel i5, 8GB RAM, SSD 256GB. Testat si merge perfect.</textarea>
                            <p id="description-error" class="mt-1.5 hidden text-xs text-em-red"></p>
                        </div>
                    </div>
                    <div class="mt-6 flex flex-wrap items-center gap-4 border-t border-hairline pt-5">
                        <button id="classify-btn" type="submit" class="beamkey beamkey-armed focus-ring rounded-sm px-5 py-3 text-[0.65rem]" aria-describedby="analysis-status">
                            <span id="classify-btn-text">Analizează anunțul</span>
                            <svg id="classify-spinner" class="hidden h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                        <p id="analysis-status" class="font-mono text-xs tabular-nums text-dim" aria-live="polite"></p>
                    </div>
                </form>
            </section>

            <section id="results" class="hidden border border-hairline" aria-labelledby="results-heading" tabindex="-1">
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-hairline bg-bench px-5 py-4 sm:px-6">
                    <div>
                        <p class="placard text-[0.6rem]">Rezultat</p>
                        <h2 id="results-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Citire clasificare</h2>
                    </div>
                    <p id="result-provider" class="font-mono text-[0.65rem] tabular-nums text-dim"></p>
                </div>

                <div class="graticule relative overflow-hidden px-5 py-8 sm:px-6 sm:py-10">
                    <div class="beam-core beam-travel absolute bottom-0 top-0 w-[3px]" aria-hidden="true"></div>
                    <div class="relative grid min-h-44 grid-cols-4 items-end gap-4 border-b border-hairline pb-7 sm:gap-8" id="spectrum-lines" role="img" aria-label="Citire spectrală a clasificării."></div>
                    <dl id="ai-results" class="relative mt-7 grid gap-x-6 gap-y-6 sm:grid-cols-2 xl:grid-cols-4"></dl>
                </div>

                <div class="border-t border-hairline bg-bench px-5 py-6 sm:px-6">
                    <div class="grid gap-6 lg:grid-cols-2">
                        <div>
                            <p class="placard text-[0.6rem]">Citire clasificator</p>
                            <dl id="keyword-results" class="mt-4 grid gap-4 border-t border-hairline pt-4 sm:grid-cols-2"></dl>
                        </div>
                        <div class="lg:border-l lg:border-hairline lg:pl-6">
                            <p class="placard text-[0.6rem]">Acord</p>
                            <dl id="comparison-results" class="mt-4 grid grid-cols-2 gap-4 border-t border-hairline pt-4"></dl>
                        </div>
                    </div>
                </div>
            </section>

            <section id="error-display" class="hidden border border-[#ff5d5d]/50 bg-bench px-5 py-5" aria-labelledby="error-heading" role="alert" tabindex="-1">
                <p class="placard text-[0.6rem] text-em-red">Eroare</p>
                <h2 id="error-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Analiza nu a reușit</h2>
                <p id="error-message" class="mt-2 break-words text-sm leading-relaxed text-em-red"></p>
            </section>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const connectionButton = document.getElementById('test-connection-btn');
        const connectionStatus = document.getElementById('connection-status');
        const form = document.getElementById('classification-form');
        const classifyButton = document.getElementById('classify-btn');
        const classifyButtonText = document.getElementById('classify-btn-text');
        const spinner = document.getElementById('classify-spinner');
        const analysisStatus = document.getElementById('analysis-status');
        const results = document.getElementById('results');
        const errorDisplay = document.getElementById('error-display');

        connectionButton.addEventListener('click', async () => {
            const originalText = connectionButton.textContent.trim();
            connectionButton.textContent = 'Se verifică…';
            connectionButton.disabled = true;
            connectionStatus.textContent = 'Se verifică conexiunea.';

            try {
                const response = await fetch('/ai-classification/test-connection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });
                const result = await response.json();

                if (result.success) {
                    connectionStatus.textContent = 'Conexiunea este disponibilă. Pagina se reîncarcă.';
                    window.location.reload();
                    return;
                }

                connectionStatus.textContent = `Conexiunea a eșuat: ${result.error ?? 'eroare necunoscută.'}`;
            } catch (error) {
                connectionStatus.textContent = `Cererea a eșuat: ${error.message}`;
            } finally {
                connectionButton.textContent = originalText;
                connectionButton.disabled = false;
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFieldErrors();
            results.classList.add('hidden');
            errorDisplay.classList.add('hidden');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            classifyButtonText.textContent = 'Se analizează…';
            spinner.classList.remove('hidden');
            classifyButton.disabled = true;
            analysisStatus.textContent = 'Analiza este în curs.';

            try {
                const response = await fetch('/ai-classification/test', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });
                const result = await response.json();

                if (result.success) {
                    displayResults(result);
                    results.classList.remove('hidden');
                    analysisStatus.textContent = 'Analiza este gata.';
                    results.focus();
                } else if (result.errors) {
                    displayFieldErrors(result.errors);
                    analysisStatus.textContent = 'Completează câmpurile marcate.';
                } else {
                    displayError(result.error ?? 'Analiza nu a putut fi finalizată.');
                }
            } catch (error) {
                displayError(`Cererea a eșuat: ${error.message}`);
            } finally {
                classifyButtonText.textContent = 'Analizează anunțul';
                spinner.classList.add('hidden');
                classifyButton.disabled = false;
            }
        });

        function displayResults(result) {
            const aiResult = result.ai_result;
            const keywordResult = result.keyword_result;
            const readings = [
                { label: 'Scor potrivire', value: `${aiResult.intent_score}%`, state: aiResult.matches_intent ? 'green' : 'red', height: Math.max(10, Math.round(aiResult.intent_score * 0.84)) },
                { label: 'Stare', value: workingLabel(aiResult.likely_working), state: workingState(aiResult.likely_working), height: conditionHeight(aiResult.likely_working) },
                { label: 'Încredere', value: percentage(aiResult.confidence), state: confidenceState(aiResult.confidence), height: confidenceHeight(aiResult.confidence) },
            ];

            renderSpectrum(readings);
            renderReadouts('ai-results', [
                { label: 'Scor potrivire', value: `${aiResult.intent_score}%`, state: aiResult.matches_intent ? 'green' : 'red' },
                { label: 'Produs căutat', value: aiResult.matches_intent ? 'Da' : 'Nu', state: aiResult.matches_intent ? 'green' : 'red' },
                { label: 'Stare', value: workingLabel(aiResult.likely_working), state: workingState(aiResult.likely_working) },
                { label: 'Încredere', value: percentage(aiResult.confidence), state: confidenceState(aiResult.confidence) },
                { label: 'Motivare', value: aiResult.reasoning, state: 'dim', text: true },
            ]);
            renderReadouts('keyword-results', [
                { label: 'Scor potrivire', value: `${keywordResult.intent_score ?? '-'}%`, state: keywordResult.matches_intent ? 'green' : 'red' },
                { label: 'Produs căutat', value: keywordResult.matches_intent ? 'Da' : 'Nu', state: keywordResult.matches_intent ? 'green' : 'red' },
                { label: 'Stare', value: workingLabel(keywordResult.likely_working), state: workingState(keywordResult.likely_working) },
                { label: 'Încredere', value: percentage(keywordResult.confidence), state: confidenceState(keywordResult.confidence) },
                { label: 'Motivare', value: keywordResult.reasoning, state: 'dim', text: true },
            ]);
            renderReadouts('comparison-results', [
                { label: 'Potrivire', value: result.comparison.intent_match ? 'Acord' : 'Diferă', state: result.comparison.intent_match ? 'green' : 'amber' },
                { label: 'Stare', value: result.comparison.working_condition_match ? 'Acord' : 'Diferă', state: result.comparison.working_condition_match ? 'green' : 'amber' },
            ]);
            document.getElementById('result-provider').textContent = 'Rezultat primit';
        }

        function renderSpectrum(readings) {
            const spectrum = document.getElementById('spectrum-lines');
            spectrum.replaceChildren();
            spectrum.setAttribute('aria-label', readings.map((reading) => `${reading.label}: ${reading.value}`).join('. '));

            readings.forEach((reading, index) => {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex min-w-0 flex-col items-center gap-3';
                const line = document.createElement('span');
                line.className = `spec-line line-ignite w-[3px] ${stateClass(reading.state)}`;
                line.style.height = `${reading.height}px`;
                line.style.animationDelay = `${0.8 + (index * 0.14)}s`;
                line.setAttribute('aria-hidden', 'true');
                const label = document.createElement('span');
                label.className = 'placard text-center text-[0.52rem]';
                label.textContent = reading.label;
                wrapper.append(line, label);
                spectrum.append(wrapper);
            });
        }

        function renderReadouts(targetId, readouts) {
            const target = document.getElementById(targetId);
            target.replaceChildren();

            readouts.forEach((readout) => {
                const wrapper = document.createElement('div');
                const term = document.createElement('dt');
                term.className = 'placard text-[0.55rem]';
                term.textContent = readout.label;
                const definition = document.createElement('dd');
                definition.className = `mt-2 break-words ${readout.text ? 'text-sm leading-relaxed text-dim' : `font-mono text-sm tabular-nums ${stateClass(readout.state)}`}`;
                definition.textContent = readout.value;
                wrapper.append(term, definition);
                target.append(wrapper);
            });
        }

        function displayError(message) {
            document.getElementById('error-message').textContent = message;
            errorDisplay.classList.remove('hidden');
            analysisStatus.textContent = 'Analiza a eșuat.';
            errorDisplay.focus();
        }

        function clearFieldErrors() {
            ['search_term', 'title', 'description'].forEach((field) => {
                const error = document.getElementById(`${field.replace('_', '-')}-error`);
                error.textContent = '';
                error.classList.add('hidden');
                document.getElementById(field).removeAttribute('aria-invalid');
            });
        }

        function displayFieldErrors(errors) {
            Object.entries(errors).forEach(([field, messages]) => {
                const error = document.getElementById(`${field.replace('_', '-')}-error`);
                const input = document.getElementById(field);
                if (error && input) {
                    error.textContent = messages[0];
                    error.classList.remove('hidden');
                    input.setAttribute('aria-invalid', 'true');
                }
            });
        }

        function percentage(value) {
            return `${(Number(value || 0) * 100).toFixed(1)}%`;
        }

        function workingLabel(value) {
            if (value === true) return 'Pare funcțional';
            if (value === false) return 'Pare defect';
            return 'Nesigur';
        }

        function workingState(value) {
            if (value === true) return 'green';
            if (value === false) return 'red';
            return 'amber';
        }

        function confidenceState(value) {
            const confidence = Number(value || 0);
            if (confidence >= 0.7) return 'green';
            if (confidence > 0) return 'amber';
            return 'red';
        }

        function confidenceHeight(value) {
            return Math.max(18, Math.round(Number(value || 0) * 100));
        }

        function conditionHeight(value) {
            if (value === true) return 78;
            if (value === false) return 42;
            return 56;
        }

        function stateClass(state) {
            return {
                green: 'text-em-green bg-[#7dffa8]',
                amber: 'text-em-amber bg-[#ffc46b]',
                red: 'text-em-red bg-[#ff5d5d]',
                dim: 'text-dim',
            }[state];
        }
    </script>
</x-app-layout>
