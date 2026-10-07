# Saworski

Marketingseite für die NTG-Prüfungsvorbereitung (Industriemeister Metall und Elektrotechnik). Vite, Tailwind CSS v4 und Vanilla JavaScript, Inhalte auf Deutsch.

## Entwicklung

- `npm run dev`: Entwicklungsserver auf http://localhost:5173/
- `npm run build`: Produktions-Build nach `dist/`
- `npm run preview`: Produktions-Build lokal ansehen

## Buchungsformular lokal testen

Das Buchungsformular sendet an `https://gemeinsamdenmeistermeistern.de/api/buchung.php`. Lokal schlägt diese Anfrage mit einem CORS-Fehler fehl, solange der Kunde `localhost` nicht freigibt.

Um die Erfolgsansicht ohne echte Anfrage zu prüfen:

1. Lege im Projektordner eine Datei `.env.local` an (Vorlage: `.env.example`).
2. Setze `VITE_MOCK_BOOKING=true`.
3. Starte `npm run dev` neu.

Der Schalter wirkt nur im Entwicklungsmodus. Im Produktions-Build ist er immer aus.

## Vor dem Launch

- **AGB-Text ist ein Platzhalter.** `agb.html` enthält einen Platzhaltertext. Der Rechtstext kommt vom Kunden und muss vor dem Launch ersetzt werden.
- **Buchungsendpunkt bestätigen.** `buchung.php` ist noch nicht live. Der Ordner `/api/` und das Antwortformat `{ success, message }` müssen mit dem Kunden bestätigt werden.
- **Situation-Optionen prüfen.** Die Schreibweise „Meisteausbildung“ bzw. „Meisterausbildung“ ist offen (siehe `SITUATION_OPTIONS` in `src/components/booking.js`).


# Kurstermine ändern oder ergänzen

1. Öffne das Repository auf GitHub und gehe zur Datei src/data/kurstermine.js.
2. Klicke oben rechts auf das Stift-Symbol (Edit this file).
3. Kopiere einen bestehenden Eintrag, füge ihn darunter ein und passe die Werte an. Ein neuer Termin braucht ein neues,einmaliges Kürzel im Feld id. Ein bestehendes Kürzel darf nie geändert werden. Beachte die Hinweise am Anfang der Datei.
4. Klicke auf "Commit changes...", wähle "Create a new branch for this commit and start a pull request" und klicke auf "Propose changes".
5. Klicke auf "Create pull request". Warte, bis der Haken beim Build-Check grün ist, und gib Nathalie Bescheid.