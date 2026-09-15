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

- `src/style.css` — single global stylesheet, imported via `<link>` in every HTML page's `<head>` (not through `main.js`). Contains the Tailwind v4 `@theme` block (design tokens: `--color-primary`, `--color-secondary-*`, `--color-neutral-*`, `--text-h1..h4`, `--text-body*`, `--font-poppins`, `--font-inter`) and self-hosted `@font-face` declarations for Poppins/Inter, pulling `.woff2` files from `public/fonts/`.
- `src/main.js` — currently just imports `style.css`; each page's `<script type="module" src="/src/main.js">` tag is the sole JS entry point.
- Pages currently reference Tailwind utility classes directly in the HTML using the custom theme tokens (e.g. `text-primary`, `text-h1`) rather than arbitrary values — prefer extending `@theme` in `style.css` over hardcoding new colors/sizes inline.
- `.env` holds `VITE_EMAILJS_PUBLIC_KEY`, `VITE_EMAILJS_SERVICE_ID`, `VITE_EMAILJS_TEMPLATE_ID` — intended for the booking form (`booking.html`) to send emails client-side via EmailJS. Not yet wired up in code.
