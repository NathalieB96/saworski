import { kurstermine } from '../data/kurstermine.js'
import { terminLabel } from '../helpers/kurstermin-label.js'
import { EMAIL_PATTERN, showFieldError, clearFieldError } from '../helpers/form-validation.js'
import { AGB_PAGE_PATH, DATENSCHUTZ_PAGE_PATH } from '../config/paths.js'
import { createListbox } from '../helpers/listbox.js'

// Buchungsformular: POST an buchung.php. Der Dateiname buchung.php ist mit dem Kunden abgestimmt.
// Der Ordner /api/ wird als gleicher Ordner wie community-beitritt.php angenommen und muss mit dem Kunden bestätigt werden.
const BOOKING_ENDPOINT_URL = 'https://gemeinsamdenmeistermeistern.de/api/buchung.php'

// Optionen. Werte sind die Seiten-Werte (begleitkurs / retreat), passend zu den anderen Modulen.
const KURS_OPTIONS = [
  { value: 'begleitkurs', label: 'NTG Begleitkurs' },
  { value: 'retreat', label: 'NTG Kurs mit Retreat' },
]

const SITUATION_OPTIONS = [
  { value: 'noch-nicht-angefangen', label: 'Meisteausbildung noch nicht angefangen' },
  { value: 'angefangen', label: 'Meisterausbildung angefangen' },
]
// TODO: Schreibweise "Meisteausbildung" bzw. "Meisterausbildung" mit dem Kunden bestätigen.

const GENERIC_ERROR = 'Etwas ist schiefgelaufen, bitte versuch es später erneut.'

const INPUT_CLASSES = 'w-full rounded-lg bg-white p-4 font-inter text-body text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white'
const CHECKBOX_CLASSES = 'size-6 shrink-0 cursor-pointer appearance-none rounded-[4px] bg-white checked:bg-[url(/icons/Check.svg)] checked:bg-no-repeat checked:bg-center'
const BUTTON_CLASSES = 'inline-flex items-center gap-2 justify-self-center rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:opacity-60'

function errorMarkup(id) {
  return `
    <p id="${id}-error" role="alert" class="flex items-center gap-2 font-inter text-body-2 text-white" hidden>
      <span class="flex shrink-0 items-center justify-center rounded-lg bg-secondary-light p-2">
        <img src="/icons/WarningDiamond.svg" alt="" class="size-6" />
      </span>
      <span data-error-text></span>
    </p>
  `
}

function requiredMark() {
  return '<span aria-hidden="true">*</span>'
}

// Das native select ist ausgeblendet. Die sichtbare Auswahl baut createListbox() darüber.
function selectMarkup(id, label, options, { required = false, withError = false }) {
  return `
    <div class="flex flex-col gap-2.5 md:col-span-2">
      <label id="${id}-label" for="${id}-trigger" class="font-inter text-body text-white">${label}${required ? requiredMark() : ''}</label>
      <div class="relative">
        <select id="${id}" name="${id}" class="sr-only" tabindex="-1" aria-hidden="true" ${required ? 'aria-required="true"' : ''}>
          ${options}
        </select>
      </div>
      ${withError ? errorMarkup(id) : ''}
    </div>
  `
}

function terminEntriesFor(kurs) {
  const isRetreat = kurs === 'retreat'
  return kurstermine.filter((termin) => (termin.retreat !== null) === isRetreat)
}

// Termin-Optionen werden aus den Termin-Daten erzeugt. Kein Termin wird hier fest eingetragen.
function terminOptionsMarkup(kurs, selectedId) {
  if (!kurs) return '<option value="" disabled selected>Bitte wähle einen Termin</option>'
  const entries = terminEntriesFor(kurs)
  if (entries.length === 0) {
    return '<option value="" disabled selected>Aktuell keine Termine verfügbar</option>'
  }
  const placeholder = `<option value="" disabled ${selectedId ? '' : 'selected'}>Bitte wähle einen Termin</option>`
  const options = entries
    .map((termin) => `<option value="${termin.id}" ${termin.id === selectedId ? 'selected' : ''}>${terminLabel(termin)}</option>`)
    .join('')
  return placeholder + options
}

// Prefill aus der URL. ?termin= hat Vorrang vor ?kurs=. Ungültige Werte werden stillschweigend ignoriert.
function readPrefill() {
  const params = new URLSearchParams(window.location.search)
  const termin = kurstermine.find((entry) => entry.id === params.get('termin'))
  if (termin) return { kurs: termin.retreat ? 'retreat' : 'begleitkurs', terminId: termin.id }
  const kurs = params.get('kurs')
  if (KURS_OPTIONS.some((option) => option.value === kurs)) return { kurs, terminId: null }
  return { kurs: null, terminId: null }
}

function markup(initial) {
  const kursPlaceholder = initial.kurs ? '' : '<option value="" disabled selected>Bitte wähle eine Option</option>'
  const kursOptions = kursPlaceholder + KURS_OPTIONS.map(
    (option) => `<option value="${option.value}" ${option.value === initial.kurs ? 'selected' : ''}>${option.label}</option>`
  ).join('')
  const situationOptions = '<option value="" disabled selected>Bitte wähle eine Option</option>' +
    SITUATION_OPTIONS.map((option) => `<option value="${option.value}">${option.label}</option>`).join('')

  return `
    <div class="mx-auto grid max-w-6xl grid-cols-1 px-6 py-12 md:py-20 lg:grid-cols-12 lg:py-32">
      <div class="flex flex-col items-center gap-10 lg:col-span-10 lg:col-start-2">
        <h1 id="buchung-heading" class="text-center font-poppins text-h1 text-white">Jetzt Platz sichern!</h1>
        <p class="text-center font-poppins text-h2 text-white">Ich brauch nur noch kurz einige Infos von dir.</p>

        <form novalidate class="grid w-full grid-cols-1 gap-5 md:grid-cols-2 md:gap-x-10" data-form>
          ${selectMarkup('kurs', 'Welchen Kurs willst du buchen?', kursOptions, { required: true, withError: true })}
          ${selectMarkup('termin', 'Kurstermin', terminOptionsMarkup(initial.kurs, initial.terminId), { required: true, withError: true })}

          <div class="flex flex-col gap-2.5">
            <label for="vorname" class="font-inter text-body text-white">Vorname${requiredMark()}</label>
            <input type="text" id="vorname" name="vorname" autocomplete="given-name" aria-required="true" class="${INPUT_CLASSES}" />
            ${errorMarkup('vorname')}
          </div>

          <div class="flex flex-col gap-2.5">
            <label for="nachname" class="font-inter text-body text-white">Nachname${requiredMark()}</label>
            <input type="text" id="nachname" name="nachname" autocomplete="family-name" aria-required="true" class="${INPUT_CLASSES}" />
            ${errorMarkup('nachname')}
          </div>

          <div class="flex flex-col gap-2.5">
            <label for="email" class="font-inter text-body text-white">E-Mail${requiredMark()}</label>
            <input type="email" id="email" name="email" autocomplete="email" aria-required="true" class="${INPUT_CLASSES}" />
            ${errorMarkup('email')}
          </div>

          <div class="flex flex-col gap-2.5">
            <label for="telefon" class="font-inter text-body text-white">Telefonnummer</label>
            <input type="tel" id="telefon" name="telefon" autocomplete="tel" class="${INPUT_CLASSES}" />
          </div>

          ${selectMarkup('situation', 'Was beschreibt deine aktuelle Situation am besten?', situationOptions, { required: false })}

          <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px" />

          <div class="flex flex-col gap-2.5 md:col-span-2">
            <div class="flex items-start gap-2.5">
              <input type="checkbox" id="datenschutz" name="datenschutz" aria-required="true" class="${CHECKBOX_CLASSES}" />
              <label for="datenschutz" class="font-inter text-body text-white">
                Ich habe die <a href="${DATENSCHUTZ_PAGE_PATH}" target="_blank" rel="noopener" class="underline">Datenschutzerklärung<span class="sr-only"> (öffnet in neuem Tab)</span></a> zur Kenntnis genommen.${requiredMark()}
              </label>
            </div>
            ${errorMarkup('datenschutz')}
          </div>

          <div class="flex flex-col gap-2.5 md:col-span-2">
            <div class="flex items-start gap-2.5">
              <input type="checkbox" id="agb" name="agb" aria-required="true" class="${CHECKBOX_CLASSES}" />
              <label for="agb" class="font-inter text-body text-white">
                Ich habe die <a href="${AGB_PAGE_PATH}" target="_blank" rel="noopener" class="underline">Allgemeinen Geschäftsbedingungen<span class="sr-only"> (öffnet in neuem Tab)</span></a> und die Widerrufsbelehrung zur Kenntnis genommen und bin damit einverstanden.
              </label>
            </div>
            ${errorMarkup('agb')}
          </div>

          <div class="flex items-start gap-2 md:col-span-2">
            <img src="/icons/Info.svg" alt="" aria-hidden="true" class="size-8 shrink-0" />
            <p class="font-inter text-body text-white">Nach deiner Anmeldung bekommst du eine Bestätigungs E-Mail. Deine Buchung wird erst nach einer Anzahlung gültig. Bitte lies alle Informationen und nächsten Schritte dazu in der E-Mail.</p>
          </div>

          <div aria-live="polite" class="text-center font-inter text-body-2 text-white md:col-span-2" data-status></div>

          <button type="submit" class="${BUTTON_CLASSES} md:col-span-2" data-submit>
            Anmelden
            <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
          </button>
        </form>
      </div>
    </div>
  `
}

function successMarkup() {
  // TODO: Erfolgsansicht aus Figma übernehmen, sobald ein Design dafür existiert. Platzhaltertext bis dahin.
  return `
    <div class="flex w-full flex-col items-center gap-6" aria-live="polite">
      <img src="/images/illustrations/Send-Email.svg" alt="" class="h-auto w-48" />
      <p class="text-center font-inter text-body text-white">Danke für deine Anmeldung! Checke dein E-Mail-Postfach für die Bestätigung und die nächsten Schritte.</p>
    </div>
  `
}

// Erfolg nur bei HTTP 2xx und gültiger JSON-Antwort mit success === true. Alles andere ist ein Fehler.
// Antwortform { success, message } ist nicht bestätigt und ist am Gratis-testen-Endpunkt angelehnt.
async function sendBooking(payload) {
  if (import.meta.env.DEV && import.meta.env.VITE_MOCK_BOOKING === 'true') {
    console.warn('Booking request mocked, nothing was sent')
    return { ok: true }
  }
  try {
    const res = await fetch(BOOKING_ENDPOINT_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const text = await res.text()
    let data = null
    try {
      data = JSON.parse(text)
    } catch {
      data = null
    }
    if (res.ok && data && data.success === true) return { ok: true }
    const message = data && typeof data.message === 'string' && data.message ? data.message : GENERIC_ERROR
    return { ok: false, message }
  } catch {
    return { ok: false, message: GENERIC_ERROR }
  }
}

export function mount(root) {
  const initial = readPrefill()
  root.id = 'buchung'
  root.setAttribute('aria-labelledby', 'buchung-heading')
  root.classList.add('bg-primary')
  root.innerHTML = markup(initial)

  const form = root.querySelector('[data-form]')
  const submitButton = root.querySelector('[data-submit]')
  const statusEl = root.querySelector('[data-status]')
  const kursSelect = root.querySelector('#kurs')
  const terminSelect = root.querySelector('#termin')
  const kursListbox = createListbox(kursSelect)
  const terminListbox = createListbox(terminSelect)
  createListbox(root.querySelector('#situation'))
  const vornameInput = root.querySelector('#vorname')
  const nachnameInput = root.querySelector('#nachname')
  const emailInput = root.querySelector('#email')
  const telefonInput = root.querySelector('#telefon')
  const situationSelect = root.querySelector('#situation')
  const datenschutzCheckbox = root.querySelector('#datenschutz')
  const agbCheckbox = root.querySelector('#agb')
  const honeypot = form.querySelector('input[name="website"]')

  // Jede Pflichtprüfung hat eine eigene Regel und eine eigene Meldung. AGB und Datenschutz sind getrennt.
  const rules = [
    { name: 'kurs', el: kursListbox.trigger, valid: () => kursSelect.value !== '', message: 'Bitte wähle einen Kurs.' },
    { name: 'termin', el: terminListbox.trigger, valid: () => terminSelect.value !== '', message: 'Bitte wähle einen Kurstermin.' },
    { name: 'vorname', el: vornameInput, valid: () => vornameInput.value.trim() !== '', message: 'Bitte gib deinen Vornamen ein.' },
    { name: 'nachname', el: nachnameInput, valid: () => nachnameInput.value.trim() !== '', message: 'Bitte gib deinen Nachnamen ein.' },
    { name: 'email', el: emailInput, valid: () => EMAIL_PATTERN.test(emailInput.value.trim()), message: 'Bitte gib eine gültige E-Mail-Adresse ein.' },
    { name: 'agb', el: agbCheckbox, valid: () => agbCheckbox.checked, message: 'Bitte bestätige die Allgemeinen Geschäftsbedingungen.' },
    { name: 'datenschutz', el: datenschutzCheckbox, valid: () => datenschutzCheckbox.checked, message: 'Bitte bestätige, dass du die Datenschutzerklärung zur Kenntnis genommen hast.' },
  ]

  const touched = new Set()

  function validateRule(rule) {
    const errorEl = root.querySelector(`#${rule.name}-error`)
    const ok = rule.valid()
    if (ok) {
      clearFieldError(rule.el, errorEl)
    } else {
      showFieldError(rule.el, errorEl, rule.message)
      touched.add(rule.name)
    }
    return ok
  }

  function revalidateIfTouched(name) {
    const rule = rules.find((entry) => entry.name === name)
    if (rule && touched.has(name)) validateRule(rule)
  }

  // Ein Feld wird erst nach dem ersten Fehler live geprüft. Vorher zeigt es keine Fehler.
  ;[vornameInput, nachnameInput, emailInput].forEach((input) => {
    input.addEventListener('blur', () => revalidateIfTouched(input.id))
    input.addEventListener('input', () => revalidateIfTouched(input.id))
  })
  ;[terminSelect, datenschutzCheckbox, agbCheckbox].forEach((el) => {
    el.addEventListener('change', () => revalidateIfTouched(el.id))
  })

  // Kurs-Auswahl: Termin-Liste neu aufbauen und den Termin auf den Platzhalter zurücksetzen.
  kursSelect.addEventListener('change', () => {
    revalidateIfTouched('kurs')
    terminSelect.innerHTML = terminOptionsMarkup(kursSelect.value, null)
    terminListbox.refresh()
    revalidateIfTouched('termin')
  })

  function collectPayload() {
    const selectedTermin = kurstermine.find((entry) => entry.id === terminSelect.value)
    const selectedSituation = SITUATION_OPTIONS.find((option) => option.value === situationSelect.value)
    return {
      course: KURS_OPTIONS.find((option) => option.value === kursSelect.value).label,
      courseID: terminSelect.value,
      courseDate: selectedTermin ? terminLabel(selectedTermin) : '',
      firstname: vornameInput.value.trim(),
      lastname: nachnameInput.value.trim(),
      email: emailInput.value.trim(),
      phone: telefonInput.value.trim(),
      situation: selectedSituation ? selectedSituation.label : '',
      termsAccepted: true,
      privacyAccepted: true,
      website: honeypot.value,
    }
  }

  function setLoading(loading) {
    submitButton.disabled = loading
    submitButton.setAttribute('aria-busy', String(loading))
  }

  // Erfolg ersetzt das Formular durch die Bestätigungsansicht.
  function showSuccess() {
    const wrapper = document.createElement('div')
    wrapper.innerHTML = successMarkup()
    form.replaceWith(wrapper.firstElementChild)
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault()
    statusEl.textContent = ''

    // Alle Regeln prüfen, damit jede Meldung sichtbar wird. Bei Fehler wird keine Anfrage gesendet.
    const results = rules.map((rule) => validateRule(rule))
    const firstInvalid = rules.find((_, index) => !results[index])
    if (firstInvalid) {
      firstInvalid.el.focus()
      return
    }

    setLoading(true)
    const outcome = await sendBooking(collectPayload())
    setLoading(false)

    if (outcome.ok) {
      showSuccess()
    } else {
      statusEl.textContent = outcome.message
    }
  })
}
