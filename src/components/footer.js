import { AGB_PAGE_PATH, DATENSCHUTZ_PAGE_PATH, WIDERRUF_PAGE_PATH } from '../config/paths.js'

const LEGAL_LINKS = [
  { label: 'AGB', href: AGB_PAGE_PATH },
  { label: 'Datenschutz', href: DATENSCHUTZ_PAGE_PATH },
  { label: 'Widerrufsbelehrung', href: WIDERRUF_PAGE_PATH },
  { label: 'Impressum', href: '/impressum.html' },
]

// Unterstrichen bei Hover, Tastaturfokus und auf der aktuellen Seite (aria-current="page").
function renderLink(link) {
  const current = window.location.pathname === link.href ? ' aria-current="page"' : ''
  return `<a href="${link.href}"${current} class="whitespace-nowrap underline-offset-4 hover:underline focus-visible:underline aria-[current=page]:underline">${link.label}</a>`
}

function markup() {
  const year = new Date().getFullYear()
  return `
    <div class="bg-primary">
      <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-6 py-6 text-body-2 font-inter text-white sm:flex-row sm:justify-between">
        <p>© ${year} Jasper Saworski</p>
        <nav aria-label="Rechtliches" class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2">
          ${LEGAL_LINKS.map(renderLink).join('\n          ')}
        </nav>
      </div>
    </div>
  `
}

export function mountFooter(root) {
  root.innerHTML = markup()
}
