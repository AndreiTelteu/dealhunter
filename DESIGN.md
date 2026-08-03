# Design

<!-- impeccable:design-schema 1 -->

## World

**The Spectrograph** — a deep-space spectral-analysis laboratory. OLX Deal Hunter's interface is the dark bench of an instrument that passes every OLX Romania listing through a beam of light and reads what it is made of: each listing is a specimen, its truth revealed as emission lines on a spectrum. The product's truth is *the machine decomposes the market's light so you read composition, not chaos*; the form's truth is *one beam, one spectrum, verdicts as spectral lines*. The category rut we refuse is the generic SaaS dark-neon hero with glowing cards — and we equally refuse our own former world, the aviation gauge cluster.

## Physical Scene

A light-sealed analysis chamber: the visitor looks into an instrument, not at a page. Ambient is near-void; the only illumination comes from the beam itself and the phosphor of readouts. Everything the interface shows is either the beam, the spectrum it produced, or the lab's annotations on both.

## Palette & Material

- **Chamber void:** true instrument black `#06080a`, with faint optical-bench paneling `#0a0e11` and engraved hairlines `#1c242a`.
- **Beam cyan:** `#59e3ff` — the scanning beam, the spectrum's carrier wave, primary action. It is light, so it may glow.
- **Emission green:** `#7dffa8` — spectral lines meaning *match / nominal / working*.
- **Emission amber:** `#ffc46b` — *shifting price / uncertain reading*.
- **Emission red:** `#ff5d5d` — *price-drop alert*. The beam's alarm line. Never decoration.
- **Text:** primary `#eaf4f6`; secondary beam-tinted `#8fa8b0` (never neutral gray).
- **Trace elements:** hairline graticules and scale markings at 30–50% opacity of `#8fa8b0`.

Material is optical glass and anodized bench-metal: hairline engraved scales, spectrum fields with fine noise-free gradients *inside the beam path only*, crisp 1px instrument rules, glow reserved for things that are literally light (beam, spectral lines, laser state). Glow always carries an offset shadow beneath it. No glassmorphism panels, no neon-on-everything, no rounded-card soup.

## Typography

- **Spectral data & readouts:** `JetBrains Mono` tabular — wavelengths, values, axis scales, sample IDs. Mono is measurement only.
- **Instrument labels:** `JetBrains Mono` uppercase, tracking `0.16em`, small — engraved placard register.
- **Display / headlines:** `Archivo` 600–800, tracking `-0.02em`, max ~4.5rem. The lab's voice: precise, unhurried.
- **Body:** `Archivo` 400/500, measure 65–72ch.

## Topology & Composition

- **The bench:** surfaces compose along one horizontal optical axis — beam enters left, spectrum resolves right. Wide screens keep this axis literal; narrow screens rotate the bench 90° (beam enters top, spectrum below).
- **Graticule:** a faint engraved grid underlies data regions, like graph film under a spectrum plate — never under text-heavy prose.
- **Navigation:** a slim instrument rail across the top; wordmark as an engraved placard with a live beam-status indicator.
- **Sections** read as successive stations on the bench: source → beam → spectrum → verdict ledger → access.

## Controls & State

- **Scanning:** the beam line is visibly alive (slow travel, faint trailing decay). A tracked search = a beam locked on.
- **Match / nominal:** emission-green spectral line, steady.
- **Caution:** amber line, subtle flicker permitted on live readings only.
- **Alert:** red line, the one moment the bench may pulse.
- **Idle / disabled:** beam parked, spectrum dimmed to engraved graticule.
- **Buttons** are beam-key switches: hairline engraved frame, cyan-lit legend, a press that travels (translate + shadow collapse), focus as a cyan ring. Keyboard-operable.

## Motion

One authored material: **the traveling beam.** The scan line moves at constant instrument speed across its field; spectral lines ignite (scale-y from baseline) as it passes them. One orchestrated pass on arrival, then the beam idles with a slow breathe. `prefers-reduced-motion`: beam rendered static mid-field, all lines fully lit, no travel.

## Signature Interaction

On the landing page, a live demonstration spectrum: the beam crosses a specimen listing and its emission lines ignite one by one — intent match, working condition, price trend — so a first-time visitor watches a listing being *understood*. Synthetic specimen, clearly labeled.

## Web Leverage

SVG spectrum fields with real geometry, CSS-animated beam travel, `prefers-reduced-motion` fallbacks, a screen-reader reading of every spectrum (label + value per line). Canvas is permitted for the beam's particle decay if it stays under budget; SVG/CSS is the default.

## Cross-Surface Reach

Every future surface inherits the bench: a single deal becomes a full-plate spectrum with its price history as an absorption trace; crawl logs become the lab notebook; admin health becomes beam-alignment status. Authenticated areas keep the same chamber black and beam cyan so the instrument never becomes a website.

## Bans

- No generic neon-glow SaaS dark mode; glow only where light physically exists.
- No amber/red as decoration; alert lines only.
- No neutral gray text on the chamber (beam-tinted secondary only).
- No gradient text, no glassmorphism, no card grids as page structure.
- No circular gauges, dials, or needle instruments — that was the previous world.
- No monospace as costume; mono measures, Archivo speaks.
