import './style.css'
import { mountHeader } from './components/header.js'
import { mountFooter } from './components/footer.js'
import { mountTestimonials } from './components/testimonials.js'

document.addEventListener('DOMContentLoaded', () => {
  const headerEl = document.querySelector('[data-component="header"]')
  const footerEl = document.querySelector('[data-component="footer"]')
  const testimonialsEl = document.querySelector('[data-component="testimonials"]')
  if (headerEl) mountHeader(headerEl)
  if (footerEl) mountFooter(footerEl)
  if (testimonialsEl) mountTestimonials(testimonialsEl)
})
