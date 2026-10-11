import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

// Live-Domain ohne www. Quelle für Canonical, sitemap.xml und robots.txt.
const SITE_URL = 'https://gemeinsamdenmeistermeistern.de'

// Alle Seiten der Website. Speist die Build-Inputs und die sitemap.xml. Neue Seiten hier eintragen.
const PAGES = {
  main: 'index.html',
  kurs1: 'ntg-begleitkurs.html',
  kurs2: 'ntg-kurs-retreat.html',
  booking: 'booking.html',
  impressum: 'impressum.html',
  agb: 'agb.html',
  datenschutz: 'datenschutz.html',
  widerruf: 'widerruf.html',
}

// Seiten werden mit .html ausgeliefert, die Startseite als "/".
function pageUrl(file) {
  return file === 'index.html' ? `${SITE_URL}/` : `${SITE_URL}/${file}`
}

// Injects <meta name="robots" content="noindex, nofollow"> into every page's <head>, staging builds only.
// Keeps the staging site out of search engines (no password on that environment) without a separate dependency.
function noindexStaging(mode) {
  return {
    name: 'noindex-staging',
    transformIndexHtml(html) {
      if (mode !== 'staging') return html
      return html.replace('<head>', '<head>\n    <meta name="robots" content="noindex, nofollow" />')
    },
  }
}

// Live build only: canonical link per page, sitemap.xml and robots.txt.
// Staging gets none of these. It relies on the noindex tag, so crawlers must be allowed to fetch it.
function seoLive(mode) {
  const isLive = mode === 'production'
  return {
    name: 'seo-live',
    apply: 'build',
    transformIndexHtml(html, ctx) {
      if (!isLive || html.includes('rel="canonical"')) return html
      const file = ctx.path.replace(/^\//, '')
      return html.replace('</head>', `  <link rel="canonical" href="${pageUrl(file)}" />\n  </head>`)
    },
    generateBundle() {
      if (!isLive) return
      const urls = Object.values(PAGES).map((file) => `  <url>\n    <loc>${pageUrl(file)}</loc>\n  </url>`).join('\n')
      this.emitFile({
        type: 'asset',
        fileName: 'sitemap.xml',
        source: `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`,
      })
      this.emitFile({
        type: 'asset',
        fileName: 'robots.txt',
        source: `User-agent: *\nAllow: /\nSitemap: ${SITE_URL}/sitemap.xml\n`,
      })
    },
  }
}

export default defineConfig(({ mode }) => ({
  plugins: [tailwindcss(), noindexStaging(mode), seoLive(mode)],
  build: {
    rollupOptions: {
      input: PAGES,
    }
  }
}))
