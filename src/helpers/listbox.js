// Eigene Auswahl-Liste (Combobox) über einem nativen <select>.
// Das native select bleibt die Quelle für Wert, Validierung und Payload. Es wird per sr-only ausgeblendet.
const TRIGGER_CLASSES = 'flex w-full items-center justify-between gap-2.5 rounded-lg bg-white p-4 text-left font-inter text-body text-neutral-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white'
const LIST_CLASSES = 'absolute left-0 top-full z-10 mt-2 flex w-full flex-col gap-1 rounded-lg border-4 border-primary/20 bg-white p-2'
const OPTION_CLASSES = 'flex items-center justify-between gap-2 rounded px-2 py-1 font-inter text-body text-neutral-900 hover:bg-primary/20'
const ACTIVE_CLASS = 'bg-primary/20'

export function createListbox(select) {
  const wrapper = select.parentElement
  const baseId = select.id
  const listId = `${baseId}-listbox`
  const labelId = `${baseId}-label`

  const trigger = document.createElement('button')
  trigger.type = 'button'
  trigger.id = `${baseId}-trigger`
  trigger.className = TRIGGER_CLASSES
  trigger.setAttribute('role', 'combobox')
  trigger.setAttribute('aria-haspopup', 'listbox')
  trigger.setAttribute('aria-expanded', 'false')
  trigger.setAttribute('aria-controls', listId)
  if (document.getElementById(labelId)) trigger.setAttribute('aria-labelledby', labelId)
  if (select.getAttribute('aria-required') === 'true') trigger.setAttribute('aria-required', 'true')
  trigger.innerHTML = `
    <span data-trigger-text class="min-w-0 whitespace-normal break-words"></span>
    <img src="/icons/CaretDown.svg" alt="" aria-hidden="true" class="size-6 shrink-0" />
  `

  const list = document.createElement('ul')
  list.id = listId
  list.setAttribute('role', 'listbox')
  list.className = LIST_CLASSES
  list.hidden = true

  wrapper.append(trigger, list)

  const triggerText = trigger.querySelector('[data-trigger-text]')
  let optionEls = []
  let activeIndex = -1

  function enabledOptions() {
    return [...select.options].filter((option) => !option.disabled)
  }

  function syncTriggerText() {
    const selected = select.selectedOptions[0]
    triggerText.textContent = selected ? selected.textContent : ''
  }

  function paintActive() {
    optionEls.forEach((el, index) => {
      const selected = el.dataset.value === select.value
      el.classList.toggle(ACTIVE_CLASS, index === activeIndex)
      el.setAttribute('aria-selected', String(selected))
      el.querySelector('[data-check]').classList.toggle('invisible', !selected)
    })
    if (activeIndex >= 0) {
      trigger.setAttribute('aria-activedescendant', optionEls[activeIndex].id)
    } else {
      trigger.removeAttribute('aria-activedescendant')
    }
  }

  function renderOptions() {
    const options = enabledOptions()
    list.innerHTML = ''
    optionEls = options.map((option, index) => {
      const li = document.createElement('li')
      li.id = `${baseId}-option-${index}`
      li.setAttribute('role', 'option')
      li.dataset.value = option.value
      li.className = OPTION_CLASSES
      const label = document.createElement('span')
      label.className = 'min-w-0 whitespace-normal break-words'
      label.textContent = option.textContent
      const check = document.createElement('img')
      check.src = '/icons/Check.svg'
      check.alt = ''
      check.setAttribute('aria-hidden', 'true')
      check.dataset.check = ''
      check.className = 'size-6 shrink-0'
      li.append(label, check)
      li.addEventListener('mousedown', (event) => event.preventDefault())
      li.addEventListener('click', () => choose(index))
      list.append(li)
      return li
    })
    syncTriggerText()
    paintActive()
  }

  function isOpen() {
    return !list.hidden
  }

  function open() {
    const options = enabledOptions()
    if (options.length === 0) return
    list.hidden = false
    trigger.setAttribute('aria-expanded', 'true')
    activeIndex = Math.max(0, options.findIndex((option) => option.value === select.value))
    paintActive()
  }

  function close() {
    list.hidden = true
    trigger.setAttribute('aria-expanded', 'false')
    trigger.removeAttribute('aria-activedescendant')
  }

  function choose(index) {
    const option = enabledOptions()[index]
    if (!option) return
    select.value = option.value
    select.dispatchEvent(new Event('change', { bubbles: true }))
    syncTriggerText()
    close()
    trigger.focus()
  }

  function move(step) {
    const count = optionEls.length
    if (count === 0) return
    activeIndex = Math.min(count - 1, Math.max(0, activeIndex + step))
    paintActive()
    optionEls[activeIndex].scrollIntoView({ block: 'nearest' })
  }

  trigger.addEventListener('click', () => (isOpen() ? close() : open()))

  trigger.addEventListener('keydown', (event) => {
    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault()
        if (!isOpen()) open()
        else move(1)
        break
      case 'ArrowUp':
        event.preventDefault()
        if (!isOpen()) open()
        else move(-1)
        break
      case 'Home':
        if (isOpen()) {
          event.preventDefault()
          activeIndex = 0
          paintActive()
        }
        break
      case 'End':
        if (isOpen()) {
          event.preventDefault()
          activeIndex = optionEls.length - 1
          paintActive()
        }
        break
      case 'Enter':
      case ' ':
        event.preventDefault()
        if (!isOpen()) open()
        else choose(activeIndex)
        break
      case 'Escape':
        if (isOpen()) {
          event.preventDefault()
          close()
        }
        break
      case 'Tab':
        close()
        break
    }
  })

  document.addEventListener('click', (event) => {
    if (isOpen() && !wrapper.contains(event.target)) close()
  })

  renderOptions()

  return {
    trigger,
    refresh() {
      renderOptions()
      if (isOpen()) open()
    },
  }
}
