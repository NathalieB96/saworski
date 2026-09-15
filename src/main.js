import './style.css'
import { mountHeader } from './components/header.js'
import { mountFooter } from './components/footer.js'

document.addEventListener('DOMContentLoaded', () => {
  const headerEl = document.querySelector('[data-component="header"]')
  const footerEl = document.querySelector('[data-component="footer"]')
  if (headerEl) mountHeader(headerEl)
  if (footerEl) mountFooter(footerEl)
})
