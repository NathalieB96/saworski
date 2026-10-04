// Gemeinsame Formular-Validierung: Fehlermeldungen unter Feldern, von "Gratis testen" und Buchungsformular genutzt.
export const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

// Zeigt die Meldung im Fehler-Element (mit data-error-text) an und markiert das Feld als ungültig.
export function showFieldError(input, errorEl, message) {
  errorEl.querySelector('[data-error-text]').textContent = message
  errorEl.hidden = false
  input.setAttribute('aria-invalid', 'true')
  input.setAttribute('aria-describedby', errorEl.id)
}

export function clearFieldError(input, errorEl) {
  errorEl.querySelector('[data-error-text]').textContent = ''
  errorEl.hidden = true
  input.removeAttribute('aria-invalid')
}
