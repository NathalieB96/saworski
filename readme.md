# Saworski – Industriemeister Metall Kurse

Website built with Vite, Tailwind CSS v4, and vanilla JavaScript.

## Tech Stack

- **Build tool:** [Vite](https://vitejs.dev/) 8 (Rolldown bundler)
- **Styling:** [Tailwind CSS](https://tailwindcss.com/) v4 via `@tailwindcss/vite`
- **JavaScript:** Vanilla JS, no framework
- **Fonts:** Poppins (headings), Inter (body), self-hosted as WOFF2

## Pages

| File | Description |
|---|---|
| `index.html` | Homepage |
| `ntg-begleitkurs.html` | Course page: NTG Begleitkurs |
| `ntg-kurs-retreat.html` | Course page: Retreat |
| `booking.html` | Course booking form |
| `impressum.html` | Legal notice |
| `datenschutz.html` | Privacy policy |

## Getting Started

### Prerequisites

- Node.js 20.19+ or 22.12+ (required by Vite 8)
- npm

### Installation

```bash
npm install
```

### Development

```bash
npm run dev
```

Opens the site at `http://localhost:5173/`.

### Build

```bash
npm run build
```

Output goes to `dist/`.

## Project Structure

- `src/main.js` — entry point; imports `style.css` and mounts the shared header/footer on `DOMContentLoaded`.
- `src/components/` — reusable UI building blocks shared across all six pages via plain JS injection (no templating plugin). Each module exports a `mount<Name>(rootEl)` function that renders markup into a placeholder element present on every page, e.g. `<header data-component="header"></header>`.
  - `header.js` — site header: logo, "Kurse" dropdown, "Kontakt" link, "Jetzt buchen" CTA, mobile hamburger menu.
  - `footer.js` — site footer: copyright, legal links (Datenschutz, Impressum).
- `public/icons/` — icon library (individual SVG files, e.g. `ArrowRight.svg`, `X.svg`, `CaretRight.svg`), referenced via `<img src="/icons/Name.svg">`.