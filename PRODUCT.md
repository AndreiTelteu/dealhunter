# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Individual deal hunters monitoring OLX Romania for personal purchases or resale opportunities. They define searches for the items they want, then review newly discovered listings and changes without repeatedly searching the marketplace by hand.

## Product Purpose

OLX Deal Hunter tracks OLX Romania listings for saved search terms, preserving listing and price snapshots over time. It helps people surface relevant opportunities and assess whether a listing is likely to match their intent and be in working condition.

## Positioning

The product combines continuous listing and price-change tracking with listing-level AI or keyword-based intent and condition classification. It turns an ephemeral marketplace search into a reviewable history rather than only reproducing a manual OLX search.

## Operating Context

Users create and activate hunted deals with specific OLX Romania search terms and optional private notes. Scheduled crawler runs collect listings and snapshots; users review hunted deals, listing details, history, and recent results. AI classification is optional, supports configured providers, and falls back to keyword analysis when unavailable.

## Capabilities and Constraints

- Track active hunted deals and automatically crawl for matching OLX Romania listings.
- Store deals, price snapshots, listing media, and classification results.
- Classify intent match, likely working condition, confidence, and reasoning using optional AI with a keyword fallback.
- Support user profile management and administrative crawl, system-health, and configuration views.
- Live crawling is explicitly controlled by enabled state, acknowledged terms and permissions, and configured time windows.
- Product experiences must use real data only. Do not fabricate listings, price history, classifications, testimonials, or performance claims.

## Brand Commitments

- Product name: OLX Deal Hunter.
- The product is focused on OLX Romania and Romanian marketplace language.

## Evidence on Hand

- Real application workflows and setup guidance: `README.md`.
- AI classification behavior, provider configuration, fallback, and stored results: `docs/ai-classification.md`.
- No approved customer testimonials, benchmarks, or marketing proof assets are present in the repository; future work must not invent them.

## Product Principles

- Make ongoing marketplace monitoring more useful than repeated manual searching.
- Preserve changes over time so users can evaluate listings with context.
- Help users judge relevance and condition while retaining transparent, real underlying data.
- Keep crawler operation explicit and respectful of configured permissions and boundaries.
- Use Romanian marketplace context and terminology where it improves search quality and comprehension.
