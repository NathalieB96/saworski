import './style.css'
import { mountHeader } from './components/header.js'
import { mountFooter } from './components/footer.js'
import { mountTestimonials } from './components/testimonials.js'

document.addEventListener('DOMContentLoaded', () => {
  const headerEl = document.querySelector('[data-component="header"]')
  const footerEl = document.querySelector('[data-component="footer"]')
  const testimonialsEl = document.querySelector('[data-component="testimonials"]')
  if (headerEl) {
    mountHeader(headerEl)
    const setHeaderHeight = () => {
      document.documentElement.style.setProperty('--header-height', `${headerEl.offsetHeight}px`)
    }
    setHeaderHeight()
    window.addEventListener('resize', setHeaderHeight)
  }
  if (footerEl) mountFooter(footerEl)
  if (testimonialsEl) mountTestimonials(testimonialsEl)
})
