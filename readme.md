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

## Deployment

Deployment runs via GitHub Actions (`.github/workflows/deploy.yml`) and uploads the contents of `dist/` to the server over `rsync`/SSH. Pull requests against `master` only run a build check (`.github/workflows/build-check.yml`) — no deployment, no secrets.

| Branch push | Builds with | Deploys to |
|---|---|---|
| `staging` | `npm run build:staging` (noindex) | the path in the `STAGING_DEPLOY_PATH` secret |
| `master` | `npm run build` | the path in the `LIVE_DEPLOY_PATH` secret, only when `LIVE_DEPLOY_ENABLED` is `true` |

**Required secrets** (repo Settings → Secrets and variables → Actions → Secrets):
- `DEPLOY_HOST`, `DEPLOY_USER` — the server to connect to.
- `DEPLOY_PORT` — optional, defaults to `22`.
- `DEPLOY_KNOWN_HOSTS` — the server's SSH host key(s), used to verify the connection (host key checking is never disabled).
- `DEPLOY_SSH_KEY` — a private key for key-based auth (preferred). If not set, the workflow falls back to `DEPLOY_PASSWORD`.
- `DEPLOY_PASSWORD` — password auth fallback, only used when `DEPLOY_SSH_KEY` is not set.
- `STAGING_DEPLOY_PATH`, `LIVE_DEPLOY_PATH` — the target directory on the server for each branch.

**Variables** (same screen, "Variables" tab):
- `DEPLOY_DRY_RUN` — when `true` or unset, deploys log what would be uploaded and change nothing on the server. Set to `false` to actually deploy.
- `LIVE_DEPLOY_ENABLED` — the live (`master`) upload only runs when this is exactly `true`. Any other value, or missing, skips the live upload (the build and the noindex guard still run).

**Rollback:** revert the merge commit on `master` and push — the next deploy ships the reverted state. There is no automatic server-side rollback.

**Never commit credentials.** Secrets are only ever referenced as `${{ secrets.NAME }}` inside the workflow — never written into a file or printed in logs.

Staging has no password protection, so every build is marked `noindex, nofollow` to keep it out of search engines; the live build never contains that tag (both are enforced by a guard step in the workflow).

# Kurstermine ändern oder ergänzen

1. Öffne das Repository auf GitHub und gehe zur Datei src/data/kurstermine.js.
2. Klicke oben rechts auf das Stift-Symbol (Edit this file).
3. Kopiere einen bestehenden Eintrag, füge ihn darunter ein und passe die Werte an. Ein neuer Termin braucht ein neues,einmaliges Kürzel im Feld id. Ein bestehendes Kürzel darf nie geändert werden. Beachte die Hinweise am Anfang der Datei.
4. Klicke auf "Commit changes...", wähle "Create a new branch for this commit and start a pull request" und klicke auf "Propose changes".
5. Klicke auf "Create pull request". Warte, bis der Haken beim Build-Check grün ist, und gib Nathalie Bescheid.