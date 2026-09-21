import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    rollupOptions: {
      input: {
        main: 'index.html',
        kurs1: 'ntg-begleitkurs.html',
        kurs2: 'ntg-kurs-retreat.html',
        booking: 'booking.html',
        impressum: 'impressum.html',
        datenschutz: 'datenschutz.html',
      }
    }
  }
})