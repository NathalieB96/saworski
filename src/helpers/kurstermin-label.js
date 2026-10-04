// Gemeinsame Datums- und Beschriftungslogik für Kurstermine (Kurstermin-Liste und Buchungsformular).
import { BOOKING_PAGE_PATH } from '../config/paths.js'

const PRUEFUNG_LABEL = { fruehjahr: 'Frühjahr', herbst: 'Herbst' }

export function formatDate(isoDate) {
  const [year, month, day] = isoDate.split('-')
  return `${day}.${month}.${year}`
}

// Gleiches Jahr: DD.MM.–DD.MM.JJJJ. Verschiedene Jahre: DD.MM.JJJJ – DD.MM.JJJJ.
export function formatDateRange({ start, ende }) {
  const [startYear, startMonth, startDay] = start.split('-')
  const [endYear, endMonth, endDay] = ende.split('-')
  if (startYear === endYear) return `${startDay}.${startMonth}.–${endDay}.${endMonth}.${endYear}`
  return `${startDay}.${startMonth}.${startYear} – ${endDay}.${endMonth}.${endYear}`
}

// Beschriftung für Termin-Auswahlen: "<Prüfung>: <Zeitraum>" plus " + Retreat: <Zeitraum>" bei Retreat-Terminen.
export function terminLabel(termin) {
  const base = `${PRUEFUNG_LABEL[termin.pruefung]}: ${formatDateRange(termin)}`
  return termin.retreat ? `${base} + Retreat: ${formatDateRange(termin.retreat)}` : base
}

// Link zur Buchungsseite, der den gewählten Kurstermin vorauswählt.
export function bookingLinkForTermin(termin) {
  return `${BOOKING_PAGE_PATH}?termin=${encodeURIComponent(termin.id)}`
}
