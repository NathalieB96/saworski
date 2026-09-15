function markup() {
  const year = new Date().getFullYear()
  return `
    <div class="bg-primary">
      <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-6 py-6 text-body-2 font-inter text-white sm:flex-row sm:justify-between">
        <p>© ${year} Jasper Saworski</p>
        <nav aria-label="Rechtliches" class="flex items-center gap-4">
          <a href="/datenschutz.html">Datenschutz</a>
          <a href="/impressum.html">Impressum</a>
        </nav>
      </div>
    </div>
  `
}

export function mountFooter(root) {
  root.innerHTML = markup()
}
