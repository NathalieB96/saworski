import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

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

export default defineConfig(({ mode }) => ({
  plugins: [tailwindcss(), noindexStaging(mode)],
  build: {
    rollupOptions: {
      input: {
        main: 'index.html',
        kurs1: 'ntg-begleitkurs.html',
        kurs2: 'ntg-kurs-retreat.html',
        booking: 'booking.html',
        impressum: 'impressum.html',
        agb: 'agb.html',
        datenschutz: 'datenschutz.html',
      }
    }
  }
}))
