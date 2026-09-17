const NAV_LINKS = {
  kurse: [
    { label: 'NTG Kurs mit Retreat', href: '/kurs-retreat.html' },
    { label: 'NTG Begleitkurs', href: '/kurs-ntg.html' },
  ],
  booking: '/booking.html',
}

function renderKurseLinks() {
  return NAV_LINKS.kurse
    .map(
      (link) => `
      <a href="${link.href}" role="menuitem" class="block whitespace-nowrap px-4 py-2 font-inter text-body-2 text-neutral-900 hover:bg-primary/20">
        ${link.label}
      </a>`
    )
    .join('')
}

function markup() {
  return `
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
      <a href="/" class="font-poppins text-lg font-semibold text-neutral-900">Saworski</a>

      <nav class="hidden items-center gap-8 md:flex" aria-label="Hauptnavigation">
        <div class="relative" data-kurse-wrapper>
          <button
            type="button"
            id="kurse-toggle"
            aria-expanded="false"
            aria-controls="kurse-menu"
            class="inline-flex items-center gap-1 font-inter text-body-2 text-neutral-900"
          >
            Kurse
            <img src="/icons/CaretRight.svg" alt="" class="size-4 rotate-90 transition-transform duration-150" data-kurse-caret />
          </button>
          <div
            id="kurse-menu"
            role="menu"
            hidden
            class="absolute left-0 top-full z-10 mt-4 min-w-max overflow-hidden rounded-lg border border-neutral-200 bg-neutral-100 shadow-lg"
          >
            ${renderKurseLinks()}
          </div>
        </div>

        <a href="${NAV_LINKS.booking}" class="inline-flex items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body-2 font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
          Jetzt buchen
          <img src="/icons/ArrowRight.svg" alt="" class="size-4" />
        </a>
      </nav>

      <button
        type="button"
        id="mobile-nav-toggle"
        aria-expanded="false"
        aria-controls="mobile-nav"
        aria-label="Menü öffnen"
        class="inline-flex size-10 items-center justify-center md:hidden"
      >
        <span class="flex flex-col items-center justify-center gap-1.5" data-hamburger-bars>
          <span class="h-0.5 w-6 bg-neutral-900"></span>
          <span class="h-0.5 w-6 bg-neutral-900"></span>
          <span class="h-0.5 w-6 bg-neutral-900"></span>
        </span>
        <img src="/icons/X.svg" alt="" class="hidden size-6" data-close-icon />
      </button>
    </div>

    <div id="mobile-nav" hidden class="border-t border-neutral-200 bg-neutral-100 px-6 py-4 md:hidden">
      <nav class="flex flex-col gap-4" aria-label="Mobile Navigation">
        ${NAV_LINKS.kurse
          .map(
            (link) => `<a href="${link.href}" class="font-inter text-body-2 text-neutral-900">${link.label}</a>`
          )
          .join('')}
        <a href="${NAV_LINKS.booking}" class="inline-flex w-fit items-center gap-2 rounded-2xl bg-secondary-light px-6 py-3 font-inter text-body-2 font-medium text-neutral-900 transition-colors duration-150 hover:bg-secondary-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-900">
          Jetzt buchen
          <img src="/icons/ArrowRight.svg" alt="" class="size-4" />
        </a>
      </nav>
    </div>
  `
}

function wireKurseDropdown(root) {
  const wrapper = root.querySelector('[data-kurse-wrapper]')
  const toggle = root.querySelector('#kurse-toggle')
  const menu = root.querySelector('#kurse-menu')
  const caret = root.querySelector('[data-kurse-caret]')

  function close() {
    toggle.setAttribute('aria-expanded', 'false')
    menu.hidden = true
    caret.classList.remove('-rotate-90')
  }

  function open() {
    toggle.setAttribute('aria-expanded', 'true')
    menu.hidden = false
    caret.classList.add('-rotate-90')
  }

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true'
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
    open()
  })

  wrapper.addEventListener('mouseleave', scheduleClose)
}

function wireMobileToggle(root) {
  const toggle = root.querySelector('#mobile-nav-toggle')
  const panel = root.querySelector('#mobile-nav')
  const bars = root.querySelector('[data-hamburger-bars]')
  const closeIcon = root.querySelector('[data-close-icon]')

  toggle.addEventListener('click', () => {
    const isOpen = toggle.getAttribute('aria-expanded') === 'true'
    toggle.setAttribute('aria-expanded', String(!isOpen))
    panel.hidden = isOpen
    bars.classList.toggle('hidden', !isOpen)
    closeIcon.classList.toggle('hidden', isOpen)
    document.body.classList.toggle('overflow-hidden', !isOpen)
  })
}

export function mountHeader(root) {
  root.innerHTML = markup()
  wireKurseDropdown(root)
  wireMobileToggle(root)
}
