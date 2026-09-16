# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Saworski – marketing site for Industriemeister Metall courses. Built with Vite 8 (Rolldown bundler), Tailwind CSS v4, and vanilla JavaScript (no framework). Content is in German.

## Commands

- `npm run dev` — start dev server at `http://localhost:5173/`
- `npm run build` — production build to `dist/`
- `npm run preview` — preview the production build

No test suite or linter is configured.

## Architecture

This is a **multi-page site**, not an SPA. Each HTML file at the repo root is a separate Vite build entry, declared explicitly in `vite.config.js` (`rollupOptions.input`): `index.html`, `kurs-ntg.html`, `kurs-retreat.html`, `booking.html`, `impressum.html`, `datenschutz.html`. When adding a new page, register it there too.

- `src/style.css` — single global stylesheet, imported via `<link>` in every HTML page's `<head>` (not through `main.js`). Contains only the Tailwind v4 `@theme` block (design tokens: `--color-primary`, `--color-secondary-*`, `--color-neutral-*`, `--color-black`/`--color-white`, `--text-h1..h4`, `--text-body*`, `--font-poppins`, `--font-inter`) and self-hosted `@font-face` declarations for Poppins/Inter, pulling `.woff2` files from `public/fonts/`.
- **Shared header/footer are not duplicated per page.** `src/components/header.js` and `src/components/footer.js` each export a `mount<Name>(rootEl)` function that builds markup as a template string and injects it via `innerHTML`. Every HTML page contains only placeholder elements (`<header data-component="header"></header>`, `<footer data-component="footer"></footer>`); `src/main.js` imports both `mount*` functions and calls them on `DOMContentLoaded`. This is plain JS, no build plugin or templating dependency — when adding a new shared UI piece with real structure/behavior (dropdowns, toggles), follow this same `mount<Name>(rootEl)` + placeholder pattern. Simple styled elements like buttons are *not* factored into JS helpers — see Coding Guidelines below.
- Icons live in `public/icons/` as individual SVG files (Phosphor-style, `viewBox="0 0 24 24"`, hardcoded `stroke="black"`) — referenced via `<img src="/icons/Name.svg">`, not an SVG sprite.
- Pages currently reference Tailwind utility classes directly in the HTML using the custom theme tokens (e.g. `text-primary`, `text-h1`) rather than arbitrary values — prefer extending `@theme` in `style.css` over hardcoding new colors/sizes inline. Color usage convention: `neutral-100` is the main page background; `neutral-200` and `primary` are used as alternate section backgrounds in places; text is `neutral-900` on light backgrounds, `white` on the `primary` blue background.
- `.env` holds `VITE_EMAILJS_PUBLIC_KEY`, `VITE_EMAILJS_SERVICE_ID`, `VITE_EMAILJS_TEMPLATE_ID` — intended for the booking form (`booking.html`) to send emails client-side via EmailJS. Not yet wired up in code.
- **Every top-level page section uses the same spacing wrapper: `mx-auto max-w-6xl px-6 py-12 lg:py-32`.** This is a fixed, site-wide convention — vertical spacing (`py-12 lg:py-32`, 128px on desktop) is not derived per-section from a Figma frame's own padding value, even if Figma specifies something else for that particular section. Apply it to every new top-level section for consistency across the whole page.

## Coding Guidelines

- Global baseline behaviors applied to root elements (`body`, `html`) are fine as plain CSS rules in `style.css` — e.g. `html { overflow-x: hidden; }`, `body { overflow-wrap: anywhere; }`, or the existing `h1, h2, h3, h4 { font-feature-settings: ... }` rule — even if a Tailwind utility exists for the same property, since the point is applying it everywhere automatically rather than repeating a class on every element.
- Component-level classes that bundle multiple utilities together (like a `.btn` or `.card`) should still default to inlined utilities, or the shared JS-function approach already used for header/footer (see Architecture above). Custom classes there remain the exception, not the default.
- Hard constraint, not just a preference: `@apply`-ing utilities into a custom class fails at build time in this Tailwind v4 setup — e.g. `.btn-primary { @apply btn ... }` errors with "Cannot apply unknown utility class". If writing a custom class, use plain CSS properties, not `@apply`.
- For simple elements like buttons — just a class list, no real behavior — repeat the utility classes directly in each page/template's markup rather than abstracting into a JS function. Simpler to read and edit at this project's size.
- For header and footer, which involve real repeated markup structure plus behavior (dropdown, hamburger toggle), share them via a single JS function per component (`mount<Name>(rootEl)`, see Architecture above) so there's one place to update nav links or structure across all six pages, instead of duplicating that markup by hand.
