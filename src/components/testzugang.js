import { EMAIL_PATTERN, showFieldError as showErrorOn, clearFieldError as clearErrorOn } from '../helpers/form-validation.js'

const MOODLE_SIGNUP_URL = 'https://gemeinsamdenmeistermeistern.de/api/community-beitritt.php'
const GENERIC_ERROR = 'Das hat leider nicht geklappt. Bitte versuche es später noch einmal.'

const FIELD_CONFIG = [
  { name: 'vorname', validate: (value) => value.trim() !== '', message: 'Bitte gib deinen Vornamen ein.' },
  { name: 'nachname', validate: (value) => value.trim() !== '', message: 'Bitte gib deinen Nachnamen ein.' },
  { name: 'email', validate: (value) => EMAIL_PATTERN.test(value.trim()), message: 'Bitte gib eine gültige E-Mail-Adresse ein.' },
]

const PRIVACY_MESSAGE = 'Bitte bestätige, dass du die Datenschutzerklärung zur Kenntnis genommen hast.'

function markup() {
  return `
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-10 px-6 py-12 md:py-20 lg:py-32">
      <h2 id="testzugang-heading" class="text-center font-poppins text-h1 text-white lg:w-10/12">Du bist noch unsicher? Gratis testen!</h2>

      <div class="flex w-full flex-col items-center gap-10" data-form-wrapper>
        <h3 class="text-center font-poppins text-h3 text-white lg:w-10/12">Jetzt anmelden und kostenlosen Testzugang zur Lernplattform sichern:</h3>

        <form novalidate class="grid w-full grid-cols-1 gap-5 md:grid-cols-2 md:gap-x-10 lg:w-10/12" data-form>
          <div class="flex flex-col gap-2.5">
            <label for="vorname" class="font-inter text-body text-white">Vorname*</label>
            <input type="text" id="vorname" name="vorname" required class="w-full rounded-lg bg-white p-4 font-inter text-body text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" />
            <p id="vorname-error" role="alert" class="flex items-center gap-2 font-inter text-body-2 text-white" hidden>
              <span class="flex shrink-0 items-center justify-center rounded-lg bg-secondary-light p-2">
                <img src="/icons/WarningDiamond.svg" alt="" class="size-6" />
              </span>
              <span data-error-text></span>
            </p>
          </div>

          <div class="flex flex-col gap-2.5">
            <label for="nachname" class="font-inter text-body text-white">Nachname*</label>
            <input type="text" id="nachname" name="nachname" required class="w-full rounded-lg bg-white p-4 font-inter text-body text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" />
            <p id="nachname-error" role="alert" class="flex items-center gap-2 font-inter text-body-2 text-white" hidden>
              <span class="flex shrink-0 items-center justify-center rounded-lg bg-secondary-light p-2">
                <img src="/icons/WarningDiamond.svg" alt="" class="size-6" />
              </span>
              <span data-error-text></span>
            </p>
          </div>

          <div class="flex flex-col gap-2.5 md:col-span-2">
            <label for="email" class="font-inter text-body text-white">E-Mail*</label>
            <input type="email" id="email" name="email" required class="w-full rounded-lg bg-white p-4 font-inter text-body text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" />
            <p id="email-error" role="alert" class="flex items-center gap-2 font-inter text-body-2 text-white" hidden>
              <span class="flex shrink-0 items-center justify-center rounded-lg bg-secondary-light p-2">
                <img src="/icons/WarningDiamond.svg" alt="" class="size-6" />
              </span>
              <span data-error-text></span>
            </p>
          </div>

          <input type="text" name="website" autocomplete="off" tabindex="-1" aria-hidden="true" class="absolute left-[-9999px]" />

          <div class="flex flex-col gap-2.5 md:col-span-2">
            <div class="flex items-start gap-2.5">
              <input type="checkbox" id="privacy" name="privacy" required class="size-6 shrink-0 cursor-pointer appearance-none rounded-[4px] bg-white checked:bg-[url('/icons/Check.svg')] checked:bg-no-repeat checked:bg-center" />
              <label for="privacy" class="font-inter text-body text-white">
                Ich habe die <a href="/datenschutz.html" class="underline">Datenschutzerklärung</a> zur Kenntnis genommen.*
              </label>
            </div>
            <p id="privacy-error" role="alert" class="flex items-center gap-2 font-inter text-body-2 text-white" hidden>
              <span class="flex shrink-0 items-center justify-center rounded-lg bg-secondary-light p-2">
                <img src="/icons/WarningDiamond.svg" alt="" class="size-6" />
              </span>
              <span data-error-text></span>
            </p>
          </div>

          <div class="flex items-start gap-2.5 md:col-span-2">
            <input type="checkbox" id="newsletter" name="newsletter" class="size-6 shrink-0 cursor-pointer appearance-none rounded-[4px] bg-white checked:bg-[url('/icons/Check.svg')] checked:bg-no-repeat checked:bg-center" />
            <label for="newsletter" class="font-inter text-body text-white">Ja, schick mir Infos zu Kursen und wann ein neuer Kurs startet.</label>
          </div>

          <div role="alert" class="font-inter text-body-2 text-white md:col-span-2" data-form-status></div>

          <button type="submit" class="inline-flex items-center gap-2 justify-self-center rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white disabled:opacity-60 md:col-span-2" data-submit>
            Testzugang sichern
            <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
          </button>
        </form>
      </div>
    </div>
  `
}

function successMarkup(wantsNewsletter) {
  return `
    <div class="flex w-full flex-col items-center gap-6">
      <img src="/images/illustrations/Send-Email.svg" alt="" class="h-auto w-48" />
      <h3 id="testzugang-success-heading" tabindex="-1" class="text-center font-poppins text-h3 text-white">Fast geschafft!</h3>
      <p class="text-center font-inter text-body text-white">Wir haben dir eine E-Mail geschickt. Klicke auf den Link darin, um deine Anmeldung zu bestätigen. Danach schalten wir deinen Testzugang frei. Bist du neu bei uns, bekommst du außerdem eine E-Mail von Moodle, um dein Passwort festzulegen.</p>
      ${wantsNewsletter ? '<p class="text-center font-inter text-body text-white">Mit dem Klick bestätigst du auch deine Newsletter-Anmeldung.</p>' : ''}
    </div>
  `
}

// Erfolg/Fehler kommen aus dem Response-Body ({ success, message }), nicht aus dem HTTP-Status.
async function sendCommunitySignup(payload) {
  if (import.meta.env.DEV && import.meta.env.VITE_MOCK_COMMUNITY) {
    console.warn('Community request mocked, nothing was sent')
    if (import.meta.env.VITE_MOCK_COMMUNITY === 'error') {
      return { success: false, message: 'Beispielfehler' }
    }
    return { success: true }
  }
  try {
    const res = await fetch(MOODLE_SIGNUP_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    const data = await res.json()
    return { success: data.success === true, message: data.message }
  } catch {
    return { success: false, message: GENERIC_ERROR }
  }
}

export function mount(root) {
  root.id = 'testzugang'
  root.setAttribute('aria-labelledby', 'testzugang-heading')
  root.classList.add('bg-primary')
  root.innerHTML = markup()

  const form = root.querySelector('[data-form]')
  const submitButton = root.querySelector('[data-submit]')
  const statusEl = root.querySelector('[data-form-status]')
  const formWrapper = root.querySelector('[data-form-wrapper]')
  const honeypot = form.querySelector('input[name="website"]')
  const privacyCheckbox = form.querySelector('#privacy')
  const newsletterCheckbox = form.querySelector('#newsletter')
  const vornameInput = form.querySelector('#vorname')
  const nachnameInput = form.querySelector('#nachname')
  const emailInput = form.querySelector('#email')

  const touchedWithError = new Set()
  let isSubmitting = false

  function fieldInput(name) {
    if (name === 'privacy') return privacyCheckbox
    return form.querySelector(`#${name}`)
  }

  function fieldValue(name) {
    const el = fieldInput(name)
    return name === 'privacy' ? el.checked : el.value
  }

  function showFieldError(name, message) {
    const el = fieldInput(name)
    const errorEl = root.querySelector(`#${name}-error`)
    showErrorOn(el, errorEl, message)
    touchedWithError.add(name)
  }

  function clearFieldError(name) {
    const el = fieldInput(name)
    const errorEl = root.querySelector(`#${name}-error`)
    clearErrorOn(el, errorEl)
  }

  function validateField(name) {
    if (name === 'privacy') {
      const valid = privacyCheckbox.checked
      valid ? clearFieldError('privacy') : showFieldError('privacy', PRIVACY_MESSAGE)
      return valid
    }
    const config = FIELD_CONFIG.find((f) => f.name === name)
    const valid = config.validate(fieldValue(name))
    valid ? clearFieldError(name) : showFieldError(name, config.message)
    return valid
  }

  function validateAll() {
    const names = [...FIELD_CONFIG.map((f) => f.name), 'privacy']
    const results = names.map((name) => ({ name, valid: validateField(name) }))
    const firstInvalid = results.find((r) => !r.valid)
    if (firstInvalid) fieldInput(firstInvalid.name).focus()
    return results.every((r) => r.valid)
  }

  ;[vornameInput, nachnameInput, emailInput].forEach((input) => {
    const name = input.id
    input.addEventListener('blur', () => {
      if (touchedWithError.has(name)) validateField(name)
    })
    input.addEventListener('input', () => {
      if (touchedWithError.has(name)) validateField(name)
    })
  })

  privacyCheckbox.addEventListener('change', () => {
    if (touchedWithError.has('privacy')) validateField('privacy')
  })

  function setLoading(loading) {
    submitButton.disabled = loading
    submitButton.setAttribute('aria-busy', String(loading))
    form.setAttribute('aria-busy', String(loading))
  }

  function showStatusError(message) {
    statusEl.textContent = message
  }

  function showSuccessView(wantsNewsletter) {
    formWrapper.innerHTML = successMarkup(wantsNewsletter)
    formWrapper.querySelector('#testzugang-success-heading').focus()
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault()
    if (isSubmitting) return

    statusEl.textContent = ''
    if (!validateAll()) return

    isSubmitting = true
    setLoading(true)

    const wantsNewsletter = newsletterCheckbox.checked
    const result = await sendCommunitySignup({
      firstname: vornameInput.value.trim(),
      lastname: nachnameInput.value.trim(),
      email: emailInput.value.trim(),
      newsletter_optin: wantsNewsletter,
      website: honeypot.value,
    })

    isSubmitting = false
    setLoading(false)

    if (result.success) {
      showSuccessView(wantsNewsletter)
    } else {
      showStatusError(result.message || GENERIC_ERROR)
    }
  })
}
