const TESTIMONIALS = [
  {
    avatar: '/images/Avatar-female-1.png',
    name: 'Lisa Cannon',
    role: 'Metallmeisterin',
    quote:
      'Ich hatte anfangs Zweifel, ob ich den Kurs neben Arbeit und Familie noch schaffe. Am Ende bin ich richtig gerne hingegangen und hatte wieder Spaß am Lernen.',
  },
  {
    avatar: '/images/Avatar-male-1.png',
    name: 'Jonas Weber',
    role: 'Metallmeister',
    quote:
      'Mir hat besonders geholfen, dass wir Inhalte immer wieder in eigenen Worten erklären sollten. Dabei habe ich schnell gemerkt, ob ich etwas wirklich verstanden habe.',
  },
  {
    avatar: '/images/Avatar-female-2.png',
    name: 'Nina Hartmann',
    role: 'Metallmeisterin',
    quote:
      'Die Atmosphäre im Kurs war super angenehm. Man konnte jederzeit Fragen stellen, ohne das Gefühl zu haben, dass man etwas längst wissen müsste.',
  },
  {
    avatar: '/images/Avatar-male-2.png',
    name: 'Tobias Krüger',
    role: 'Metallmeister',
    quote:
      'NTG war definitiv mein Angstfach. Nach ein paar Terminen war es zwar immer noch nicht mein Lieblingsfach, aber ich wusste endlich, wie ich an die Aufgaben rangehen muss.',
  },
  {
    avatar: '/images/Avatar-female-3.png',
    name: 'Sarah Bergmann',
    role: 'Metallmeisterin',
    quote:
      'Wenn etwas nicht verständlich war, wurde es einfach nochmal anders erklärt. Gerade die Beispiele aus der Praxis haben mir dabei sehr geholfen.',
  },
  {
    avatar: '/images/Avatar-male-3.png',
    name: 'Daniel Schuster',
    role: 'Metallmeister',
    quote:
      'Vorher wusste ich bei vielen Aufgaben gar nicht, wo ich anfangen soll. Heute gehe ich deutlich strukturierter an die Aufgaben ran und habe viel mehr Sicherheit.',
  },
]

function renderCard(testimonial, index) {
  return `
    <article role="group" aria-roledescription="slide" aria-label="${index + 1} von ${TESTIMONIALS.length}" class="flex h-auto w-72 shrink-0 snap-start flex-col gap-6 rounded-tl-3xl rounded-tr-3xl rounded-bl-3xl border-2 border-black bg-white p-6 sm:w-80 sm:p-8">
      <div class="flex items-center gap-4">
        <img src="${testimonial.avatar}" alt="" class="size-18 shrink-0 rounded-full border-[6px] border-primary object-cover md:border-[8px]" />
        <div class="flex flex-col">
          <p class="font-inter text-body text-black">${testimonial.name}</p>
          <p class="font-inter text-body text-neutral-900/60">${testimonial.role}</p>
        </div>
      </div>
      <p class="font-inter text-body text-black">&ldquo;${testimonial.quote}&rdquo;</p>
    </article>
  `
}

function markup() {
  return `
    <div class="mx-auto max-w-6xl px-6 py-12 md:py-20 lg:py-32">
      <h2 id="testimonials-heading" class="font-poppins text-h1 text-center text-black">Das sagen meine Kursbesucher</h2>

      <div role="region" aria-roledescription="carousel" aria-labelledby="testimonials-heading" class="mt-10">
        <div data-track tabindex="0" class="no-scrollbar flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth pb-2 -mr-[calc((100vw-100%)/2)]">
          ${TESTIMONIALS.map(renderCard).join('')}
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <button
            type="button"
            data-prev
            aria-label="Vorherige Bewertung"
            class="flex size-12 items-center justify-center rounded-full text-primary transition-colors hover:bg-primary/10"
          >
            <img src="/icons/CaretLeft.svg" alt="" class="size-6" />
          </button>
          <button
            type="button"
            data-next
            aria-label="Nächste Bewertung"
            class="flex size-12 items-center justify-center rounded-full text-primary transition-colors hover:bg-primary/10"
          >
            <img src="/icons/CaretRight.svg" alt="" class="size-6" />
          </button>
        </div>
      </div>
    </div>
  `
}

function wireCarousel(root) {
  const track = root.querySelector('[data-track]')
  const prevButton = root.querySelector('[data-prev]')
  const nextButton = root.querySelector('[data-next]')

  function stepSize() {
    const firstCard = track.querySelector('article')
    const gap = parseFloat(getComputedStyle(track).columnGap || '0')
    return firstCard.getBoundingClientRect().width + gap
  }

  function goNext() {
    const max = track.scrollWidth - track.clientWidth
    if (track.scrollLeft >= max - 1) {
      track.scrollTo({ left: 0, behavior: 'auto' })
    } else {
      track.scrollBy({ left: stepSize(), behavior: 'smooth' })
    }
  }

  function goPrev() {
    const max = track.scrollWidth - track.clientWidth
    if (track.scrollLeft <= 0) {
      track.scrollTo({ left: max, behavior: 'auto' })
    } else {
      track.scrollBy({ left: -stepSize(), behavior: 'smooth' })
    }
  }

  prevButton.addEventListener('click', goPrev)
  nextButton.addEventListener('click', goNext)
}

export function mountTestimonials(root) {
  root.innerHTML = markup()
  wireCarousel(root)
}
