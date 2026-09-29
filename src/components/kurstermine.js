import { kurstermine } from '../data/kurstermine.js'

const FRUEHBUCHER_TAGE = 56

// Platzhalter-URL: durch das echte Formular ersetzen, sobald verfügbar.
const WUNSCHTERMIN_FORM_URL = '#wunschtermin' // TODO: replace with real form URL once available

const PRUEFUNG_OPTIONS = [
  { value: 'fruehjahr', label: 'Frühjahr' },
  { value: 'herbst', label: 'Herbst' },
]

const TAGESZEIT_OPTIONS = [
  { value: 'alle', label: 'Alle' },
  { value: 'frueh', label: 'Früh' },
  { value: 'spaet', label: 'Spät' },
  { value: 'wechsel', label: 'Früh-Spät Wechsel' },
]

const RHYTHMUS_OPTIONS = [
  { value: 'alle', label: 'Alle' },
  { value: 'woechentlich', label: 'Wöchentlich' },
  { value: '14-taegig', label: '14-tägig' },
]

const RHYTHMUS_LABEL = {
  woechentlich: 'Wöchentlich',
  '14-taegig': '14-tägig',
}

const TAGESZEIT_BADGE = {
  frueh: { label: 'Früh', icon: '/icons/SunHorizon.svg', bg: 'bg-badge-fruh' },
  spaet: { label: 'Spät', icon: '/icons/Moon.svg', bg: 'bg-badge-spaet' },
  wechsel: { label: 'Früh/Spät', icon: '/icons/ArrowsLeftRight.svg', bg: 'bg-badge-wechsel' },
}

function derivedTageszeit(termin) {
  const hasFrueh = termin.zeiten.some((z) => z.tageszeit === 'frueh')
  const hasSpaet = termin.zeiten.some((z) => z.tageszeit === 'spaet')
  if (hasFrueh && hasSpaet) return 'wechsel'
  return hasFrueh ? 'frueh' : 'spaet'
}

function isFruehbucher(termin) {
  const cutoff = new Date(termin.start)
  cutoff.setDate(cutoff.getDate() - FRUEHBUCHER_TAGE)
  return new Date() <= cutoff
}

function price(termin) {
  return isFruehbucher(termin)
    ? { current: termin.fruehbucherpreis, original: termin.preis }
    : { current: termin.preis, original: null }
}

function formatDate(isoDate) {
  const [year, month, day] = isoDate.split('-')
  return `${day}.${month}.${year}`
}

function rhythmusLabel(termin) {
  const base = RHYTHMUS_LABEL[termin.rhythmus]
  return termin.anzahlTermine ? `${base} · Vorbereitung · ${termin.anzahlTermine} Termine` : base
}

function matchesFilters(termin, filters) {
  if (termin.pruefung !== filters.pruefung) return false
  if (filters.tageszeit !== 'alle' && derivedTageszeit(termin) !== filters.tageszeit) return false
  if (filters.rhythmus !== 'alle' && termin.rhythmus !== filters.rhythmus) return false
  return true
}

function optionButtonsHtml(options, activeValue, variant) {
  return options
    .map((opt) => {
      const active = opt.value === activeValue
      if (variant === 'toggle') {
        return `
          <button type="button" role="radio" aria-checked="${active}" tabindex="${active ? '0' : '-1'}" data-value="${opt.value}" class="flex-1 rounded-lg p-4 text-center font-inter text-body transition-colors ${active ? 'bg-white text-neutral-900' : 'text-white'}">
            <strong>${opt.label}</strong>
          </button>
        `
      }
      return `
        <button type="button" role="radio" aria-checked="${active}" tabindex="${active ? '0' : '-1'}" data-value="${opt.value}" class="inline-flex items-center gap-2.5 rounded-lg border-2 px-2 py-1 font-inter text-body transition-colors ${active ? 'border-secondary-light bg-secondary-light text-black' : 'border-white text-white'}">
          ${active ? '<img src="/icons/Check.svg" alt="" class="size-4" />' : ''}
          ${opt.label}
        </button>
      `
    })
    .join('')
}

function renderBadge(termin) {
  const badge = TAGESZEIT_BADGE[derivedTageszeit(termin)]
  return `
    <span class="inline-flex w-fit items-center gap-1 rounded-2xl ${badge.bg} px-2 py-1">
      <img src="${badge.icon}" alt="" class="size-6" />
      <span class="font-inter text-body-2 text-black">${badge.label}</span>
    </span>
  `
}

function renderZeitenText(termin) {
  return termin.zeiten.map((z) => `${z.uhrzeit} Uhr`).join('<br>')
}

function renderDateBlock(termin) {
  return `
    <div class="flex flex-col gap-1">
      <p class="font-inter text-body-2 text-black"><strong>Start: ${formatDate(termin.start)}</strong></p>
      <p class="font-inter text-body-2 text-black">Ende: ${formatDate(termin.ende)}</p>
    </div>
  `
}

function renderRetreatColumn(termin) {
  return `
    <div class="flex items-center gap-2">
      <img src="/icons/Suitcase.svg" alt="" class="size-8 shrink-0" />
      ${renderDateBlock(termin.retreat)}
    </div>
  `
}

function renderPrice(termin, alignEnd) {
  const { current, original } = price(termin)
  return `
    <div class="flex flex-col ${alignEnd ? 'items-end' : 'items-start'} gap-1">
      <p class="font-inter text-body-2 text-black">${current} €${original ? '*' : ''}</p>
      ${original ? `<p class="font-inter text-body-2 text-black/50 line-through">${original} €</p>` : ''}
    </div>
  `
}

const BUTTON_CLASSES =
  'inline-flex items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900'

function desktopOuterClass(termin) {
  return termin.retreat
    ? 'hidden xl:grid xl:w-full xl:items-center xl:gap-4'
    : 'hidden lg:grid lg:w-full lg:items-center lg:gap-6'
}

function desktopGridColsClass(termin) {
  return termin.retreat
    ? 'xl:grid-cols-[10rem_9rem_12rem_12rem_6rem_1fr]'
    : 'lg:grid-cols-[11rem_11rem_14rem_6rem_1fr]'
}

function buttonJustifyClass(termin) {
  return termin.retreat ? 'xl:justify-self-end' : 'lg:justify-self-end'
}

function renderDesktopRow(termin) {
  return `
    <div class="${desktopOuterClass(termin)} ${desktopGridColsClass(termin)}">
      <div>
        <p class="font-poppins text-h3 text-black"><strong>${termin.wochentag}</strong></p>
        <p class="font-inter text-body-2 text-black">${rhythmusLabel(termin)}</p>
      </div>
      <div class="flex flex-col items-start gap-2">
        ${renderBadge(termin)}
        <p class="font-inter text-body-2 text-black">${renderZeitenText(termin)}</p>
      </div>
      <div class="flex items-center gap-2">
        <img src="/icons/CalendarDots.svg" alt="" class="size-8 shrink-0" />
        ${renderDateBlock(termin)}
      </div>
      ${termin.retreat ? renderRetreatColumn(termin) : ''}
      ${renderPrice(termin, false)}
      <a href="/booking.html" class="${BUTTON_CLASSES} ${buttonJustifyClass(termin)}">
        Platz sichern
        <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
      </a>
    </div>
  `
}

function mobileCardClass(termin) {
  return termin.retreat ? 'flex flex-col gap-5 xl:hidden' : 'flex flex-col gap-5 lg:hidden'
}

function renderMobileCard(termin) {
  return `
    <div class="${mobileCardClass(termin)}">
      <div class="flex items-start justify-between gap-4">
        <p class="font-poppins text-h3 text-black"><strong>${termin.wochentag}</strong></p>
        ${renderBadge(termin)}
      </div>
      <div class="flex items-start justify-between gap-4">
        <p class="font-inter text-body-2 text-black">${rhythmusLabel(termin)}</p>
        <p class="font-inter text-body-2 text-right text-black">${renderZeitenText(termin)}</p>
      </div>
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-center gap-2">
          <img src="/icons/CalendarDots.svg" alt="" class="size-8 shrink-0" />
          ${renderDateBlock(termin)}
        </div>
        ${renderPrice(termin, true)}
      </div>
      ${termin.retreat ? renderRetreatColumn(termin) : ''}
      <a href="/booking.html" class="${BUTTON_CLASSES} w-full justify-center">
        Platz sichern
        <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
      </a>
    </div>
  `
}

function courseWrapperClass(termin) {
  return termin.retreat
    ? 'flex flex-col gap-5 rounded-[32px] bg-white p-5 shadow-[0px_0px_30px_0px_rgba(0,0,0,0.1)] xl:gap-0 xl:rounded-none xl:bg-transparent xl:p-6 xl:shadow-none'
    : 'flex flex-col gap-5 rounded-[32px] bg-white p-5 shadow-[0px_0px_30px_0px_rgba(0,0,0,0.1)] lg:gap-0 lg:rounded-none lg:bg-transparent lg:p-6 lg:shadow-none'
}

function renderCourse(termin) {
  return `
    <div class="${courseWrapperClass(termin)}">
      ${renderMobileCard(termin)}
      ${renderDesktopRow(termin)}
    </div>
  `
}

function renderList(filtered) {
  if (filtered.length === 0) {
    return `<p class="p-6 text-center font-inter text-body-2 text-black">Keine Termine gefunden.</p>`
  }
  return filtered.map(renderCourse).join('')
}

function renderToggle(state) {
  return `
    <div role="radiogroup" aria-label="Prüfung wählen" data-group="pruefung" class="flex w-full max-w-xl gap-4 rounded-2xl border-4 border-white bg-primary-light p-2">
      ${optionButtonsHtml(PRUEFUNG_OPTIONS, state.pruefung, 'toggle')}
    </div>
  `
}

function renderFilterGroup(name, label, options, state) {
  return `
    <div class="flex flex-col items-center gap-3 md:items-start">
      <span id="${name}-label" class="font-inter text-body text-white"><strong>${label}</strong></span>
      <div role="radiogroup" aria-labelledby="${name}-label" data-group="${name}" class="flex flex-wrap justify-center gap-3">
        ${optionButtonsHtml(options, state[name], 'pill')}
      </div>
    </div>
  `
}

function listWrapperClass(seite) {
  return seite === 'retreat'
    ? 'flex w-full flex-col gap-4 xl:gap-0 xl:divide-y xl:divide-neutral-900 xl:overflow-hidden xl:rounded-[32px] xl:bg-neutral-100'
    : 'flex w-full flex-col gap-4 lg:gap-0 lg:divide-y lg:divide-neutral-900 lg:overflow-hidden lg:rounded-[32px] lg:bg-neutral-100'
}

function markup(state) {
  return `
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-10 px-6 py-12 md:py-20 lg:py-32">
      <h2 id="platz-sichern-heading" class="text-center font-poppins text-h1 text-white">Jetzt Platz sichern</h2>

      <div class="flex w-full flex-col items-center gap-4">
        <h4 class="font-inter text-h4 text-white">Wähle zuerst deine Prüfung</h4>
        ${renderToggle(state)}
      </div>

      <div class="flex w-full flex-col items-center gap-6 md:flex-row md:justify-center md:gap-10">
        ${renderFilterGroup('tageszeit', 'Tageszeit', TAGESZEIT_OPTIONS, state)}
        ${renderFilterGroup('rhythmus', 'Rhythmus', RHYTHMUS_OPTIONS, state)}
      </div>

      <p class="sr-only" role="status" aria-live="polite" data-count></p>

      <div class="flex w-full flex-col gap-3">
        <div class="${listWrapperClass(state.seite)}" data-list></div>
        <p class="w-full text-right font-inter text-body-2 text-white">*Frühbucherpreis gültig bis 8 Wochen vor Kursstart.</p>
      </div>

      <div class="flex flex-col items-center gap-6 text-center">
        <p class="font-inter text-body text-white">Du hast Interesse, aber die angebotenen Termine passen dir nicht? Teil uns mit, welche Termine dir passen würden – wir lassen dich wissen, sobald ein Kurs zu einem deiner Wunschtermine startet.</p>
        <a href="${WUNSCHTERMIN_FORM_URL}" class="inline-flex items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
          Wunschtermin
          <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
        </a>
      </div>
    </div>
  `
}

function updateList(root, state) {
  const base = kurstermine.filter((termin) =>
    state.seite === 'retreat' ? termin.retreat !== null : termin.retreat === null
  )
  const filtered = base.filter((termin) => matchesFilters(termin, state))
  root.querySelector('[data-list]').innerHTML = renderList(filtered)
  root.querySelector('[data-count]').textContent = `${filtered.length} Termine gefunden`
}

function updateGroup(root, groupName, options, variant, state) {
  const groupEl = root.querySelector(`[data-group="${groupName}"]`)
  groupEl.innerHTML = optionButtonsHtml(options, state[groupName], variant)
  groupEl.querySelector('[aria-checked="true"]')?.focus()
}

function wireGroup(root, groupName, options, variant, state, onChange) {
  const groupEl = root.querySelector(`[data-group="${groupName}"]`)

  function select(value) {
    if (state[groupName] === value) return
    state[groupName] = value
    updateGroup(root, groupName, options, variant, state)
    onChange()
  }

  groupEl.addEventListener('click', (event) => {
    const btn = event.target.closest('[role="radio"]')
    if (!btn) return
    select(btn.dataset.value)
  })

  groupEl.addEventListener('keydown', (event) => {
    if (!['ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown'].includes(event.key)) return
    event.preventDefault()
    const currentIndex = options.findIndex((opt) => opt.value === state[groupName])
    const dir = event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1
    const nextIndex = (currentIndex + dir + options.length) % options.length
    select(options[nextIndex].value)
  })
}

export function mount(root, { seite }) {
  const state = { pruefung: 'fruehjahr', tageszeit: 'alle', rhythmus: 'alle', seite }
  root.id = 'platz-sichern'
  root.setAttribute('aria-labelledby', 'platz-sichern-heading')
  root.className = 'bg-primary'
  root.innerHTML = markup(state)
  updateList(root, state)

  wireGroup(root, 'pruefung', PRUEFUNG_OPTIONS, 'toggle', state, () => updateList(root, state))
  wireGroup(root, 'tageszeit', TAGESZEIT_OPTIONS, 'pill', state, () => updateList(root, state))
  wireGroup(root, 'rhythmus', RHYTHMUS_OPTIONS, 'pill', state, () => updateList(root, state))
}
