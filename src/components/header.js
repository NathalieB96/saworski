import { BOOKING_PAGE_PATH } from '../config/paths.js'

const NAV_LINKS = {
  kurse: [
    { label: 'NTG Begleitkurs', href: '/ntg-begleitkurs.html' },
    { label: 'NTG Kurs mit Retreat', href: '/ntg-kurs-retreat.html' },
  ],
}

// Auf den Kursseiten steht data-kurs am <body>. Dann verlinkt "Jetzt buchen" mit ?kurs=, sonst auf die Buchungsseite.
function bookingHref() {
  const kurs = document.body.dataset.kurs
  return kurs ? `${BOOKING_PAGE_PATH}?kurs=${kurs}` : BOOKING_PAGE_PATH
}

function renderKurseLinkRow(link, isLast) {
  return `
    <div${isLast ? '' : ' class="border-b border-neutral-500 pb-2"'}>
      <a href="${link.href}" role="menuitem" class="flex items-center justify-between gap-4 rounded-md px-2 py-1.5 -mx-2 font-inter text-body text-black transition-colors duration-150 hover:bg-primary/10">
        ${link.label}
        <img src="/icons/CaretRight.svg" alt="" class="size-6 shrink-0" />
      </a>
    </div>
  `
}

function renderMobileKurseLinkRow(link, isLast) {
  return `
    <div${isLast ? '' : ' class="border-b border-neutral-500 pb-2"'}>
      <a href="${link.href}" class="flex items-center justify-between gap-4 font-inter text-body-2 text-black">
        ${link.label}
        <img src="/icons/CaretRight.svg" alt="" class="size-6 shrink-0" />
      </a>
    </div>
  `
}

function markup() {
  const links = NAV_LINKS.kurse
  return `
    <div class="flex items-center justify-between bg-neutral-200 p-4 md:px-10 md:py-4">
      <a href="/" class="font-poppins text-[32px] font-bold text-black">GDM2</a>

      <nav class="hidden items-center gap-9 md:flex" aria-label="Hauptnavigation">
        <div class="relative" data-kurse-wrapper>
          <button
            type="button"
            id="kurse-toggle"
            aria-expanded="false"
            aria-controls="kurse-menu"
            class="flex items-center gap-2.5 font-inter text-body text-black"
          >
            Kurse
            <img src="/icons/CaretRight.svg" alt="" class="size-4 rotate-90 transition-transform duration-150" data-kurse-caret />
          </button>
          <div
            id="kurse-menu"
            role="menu"
            hidden
            class="absolute left-0 top-full z-10 mt-7 flex w-max flex-col gap-2 overflow-clip rounded-bl-lg rounded-br-lg bg-neutral-200 p-4"
          >
            ${links.map((link, i) => renderKurseLinkRow(link, i === links.length - 1)).join('')}
          </div>
        </div>

        <a href="${bookingHref()}" class="inline-flex items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
          Jetzt buchen
          <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
        </a>
      </nav>

      <div class="flex items-center gap-3 md:hidden">
        <a href="${bookingHref()}" class="inline-flex items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
          Jetzt buchen
          <img src="/icons/ArrowRight.svg" alt="" class="size-6" />
        </a>
        <button
          type="button"
          id="mobile-nav-toggle"
          aria-expanded="false"
          aria-controls="mobile-nav"
          aria-label="Menü öffnen"
          class="nav-icon relative size-8 shrink-0 cursor-pointer"
        >
          <span aria-hidden="true" class="nav-icon-bar"></span>
          <span aria-hidden="true" class="nav-icon-bar"></span>
          <span aria-hidden="true" class="nav-icon-bar"></span>
        </button>
      </div>
    </div>

    <div id="mobile-nav" aria-hidden="true" class="h-0 overflow-hidden bg-neutral-100 md:hidden">
      <div class="flex flex-col gap-5 px-4 py-5">
        <p class="font-inter text-body font-semibold text-black">Kurse</p>
        <nav class="flex flex-col gap-2" aria-label="Mobile Navigation">
          ${links.map((link, i) => renderMobileKurseLinkRow(link, i === links.length - 1)).join('')}
        </nav>
      </div>
    </div>
  `
}

function wireKurseDropdown(root) {
  const wrapper = root.querySelector('[data-kurse-wrapper]')
  const toggle = root.querySelector('#kurse-toggle')
  const menu = root.querySelector('#kurse-menu')
  const caret = root.querySelector('[data-kurse-caret]')

  let openedByHover = false

  function close() {
    toggle.setAttribute('aria-expanded', 'false')
    menu.hidden = true
    caret.classList.remove('-rotate-90')
    openedByHover = false
  }

  function open() {
    toggle.setAttribute('aria-expanded', 'true')
    menu.hidden = false
    caret.classList.add('-rotate-90')
  }

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true'
    if (isOpen && openedByHover) {
      openedByHover = false
      return
    }
    isOpen ? close() : open()
  })

  document.addEventListener('click', (event) => {
    if (!wrapper.contains(event.target)) close()
  })

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close()
  })

  let closeTimer

  function scheduleClose() {
    closeTimer = window.setTimeout(close, 150)
  }

  function cancelClose() {
    window.clearTimeout(closeTimer)
  }

  wrapper.addEventListener('mouseenter', () => {
    cancelClose()
    if (toggle.getAttribute('aria-expanded') !== 'true') {
      openedByHover = true
    }
    open()
  })

  wrapper.addEventListener('mouseleave', scheduleClose)
}

function wireMobileToggle(root) {
  const toggle = root.querySelector('#mobile-nav-toggle')
  const panel = root.querySelector('#mobile-nav')
  function setOpen(open) {
    toggle.setAttribute('aria-expanded', String(open))
    toggle.setAttribute('aria-label', open ? 'Menü schließen' : 'Menü öffnen')
    toggle.classList.toggle('nav-icon-open', open)
    panel.setAttribute('aria-hidden', String(!open))
    panel.style.height = open ? '100vh' : '0'
  }

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true'
    setOpen(!isOpen)
  })

  panel.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setOpen(false))
  })

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') setOpen(false)
  })
}

export function mountHeader(root) {
  root.innerHTML = markup()
  wireKurseDropdown(root)
  wireMobileToggle(root)
}
