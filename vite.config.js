import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    rollupOptions: {
      input: {
        main: 'index.html',
        kurs1: 'kurs-ntg.html',
        kurs2: 'kurs-retreat.html',
        booking: 'booking.html',
        impressum: 'impressum.html',
        datenschutz: 'datenschutz.html',
      }
    }
  }
})