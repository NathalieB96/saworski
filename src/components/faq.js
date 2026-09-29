import { faqBegleitkurs, faqRetreat } from '../data/faq.js'

function itemsFor(seite) {
  return seite === 'retreat' ? faqRetreat : faqBegleitkurs
}

function rowId(seite, index, part) {
  return `faq-${part}-${seite}-${index}`
}

function renderRow(seite, item, index, openIndex) {
  const open = index === openIndex
  const questionId = rowId(seite, index, 'q')
  const answerId = rowId(seite, index, 'a')
  return `
    <div>
      <h3>
        <button type="button" id="${questionId}" data-index="${index}" aria-expanded="${open}" aria-controls="${answerId}" class="flex w-full items-start justify-between gap-10 py-6 text-left">
          <span class="font-poppins text-h3 text-neutral-900">${item.frage}</span>
          <span class="flex shrink-0 items-center justify-center rounded-lg bg-primary/10 p-2">
            <img src="/icons/${open ? 'X.svg' : 'Plus.svg'}" alt="" class="size-6" data-icon />
          </span>
        </button>
      </h3>
      <div id="${answerId}" role="region" aria-labelledby="${questionId}" class="overflow-hidden transition-[max-height] duration-300 ease-in-out" style="max-height: ${open ? 'none' : '0px'}" data-panel>
        <p class="pb-6 font-inter text-body text-neutral-900">${item.antwort}</p>
      </div>
    </div>
  `
}

function markup(seite, openIndex) {
  const items = itemsFor(seite)
  return `
    <div class="mx-auto flex max-w-6xl flex-col gap-10 px-6 py-12 md:py-20 lg:py-32">
      <h2 id="faq-heading" class="font-poppins text-h1 text-neutral-900">Häufig gestellte Fragen</h2>
      <div class="flex flex-col divide-y divide-neutral-500" data-list>
        ${items.map((item, index) => renderRow(seite, item, index, openIndex)).join('')}
      </div>
    </div>
  `
}

function syncPanelHeight(panel, open) {
  panel.style.maxHeight = open ? `${panel.scrollHeight}px` : '0px'
}

function updateOpenState(root, seite, openIndex) {
  const items = itemsFor(seite)
  items.forEach((_, index) => {
    const open = index === openIndex
    const questionButton = root.querySelector(`#${rowId(seite, index, 'q')}`)
    const panel = root.querySelector(`#${rowId(seite, index, 'a')}`)
    const icon = questionButton.querySelector('[data-icon]')
    questionButton.setAttribute('aria-expanded', String(open))
    syncPanelHeight(panel, open)
    icon.src = open ? '/icons/X.svg' : '/icons/Plus.svg'
  })
}

export function mount(root, { seite }) {
  let openIndex = 0
  root.id = 'faq'
  root.setAttribute('aria-labelledby', 'faq-heading')
  root.innerHTML = markup(seite, openIndex)

  const items = itemsFor(seite)
  items.forEach((_, index) => {
    const panel = root.querySelector(`#${rowId(seite, index, 'a')}`)
    syncPanelHeight(panel, index === openIndex)
  })

  root.querySelector('[data-list]').addEventListener('click', (event) => {
    const button = event.target.closest('button[data-index]')
    if (!button) return
    const index = Number(button.dataset.index)
    openIndex = index === openIndex ? null : index
    updateOpenState(root, seite, openIndex)
  })

  window.addEventListener('resize', () => {
    if (openIndex === null) return
    const panel = root.querySelector(`#${rowId(seite, openIndex, 'a')}`)
    syncPanelHeight(panel, true)
  })
}
