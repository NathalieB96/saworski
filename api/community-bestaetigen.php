<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GDMM – COMMUNITY BESTÄTIGEN
|--------------------------------------------------------------------------
|
| GET:
| Zeigt die Bestätigungsseite an.
|
| POST:
| Bestätigt den Communitybeitritt.
| Erst danach erfolgt die Moodle-Registrierung.
|
| Optional:
| Newsletter-Einwilligung dauerhaft dokumentieren.
|
*/


// =========================================================
// 1. SICHERHEITS-HEADER
// =========================================================

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');


// =========================================================
// 2. PRIVATE DATEIEN LADEN
// =========================================================

$privateDir = dirname(__DIR__, 2) . '/private';

require_once $privateDir . '/community-lib.php';

$config = require $privateDir . '/community-config.php';


// =========================================================
// 3. HTML-SEITE AUSGEBEN
// =========================================================

function communityPage(
    string $title,
    string $content,
    int $status = 200
): never {

    http_response_code($status);

    $safeTitle = htmlspecialchars(
        $title,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    echo <<<HTML
<!doctype html>
<html lang="de">

<head>
    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>{$safeTitle}</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f5f8;
            color: #1f2937;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        main {
            max-width: 540px;
            margin: 60px auto;
            padding: 32px;
            background: #ffffff;
            border-radius: 14px;
        }

        h1 {
            color: #1565c0;
            font-size: 26px;
            line-height: 1.3;
        }

        p {
            margin: 16px 0;
        }

        button,
        .button {
            display: block;
            width: 100%;
            margin-top: 14px;
            padding: 14px 18px;

            background: #2e7d32;
            color: #ffffff;

            border: 0;
            border-radius: 7px;

            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;

            cursor: pointer;
        }

        button.secondary {
            background: #eef2f6;
            color: #1f2937;
        }

        .info {
            padding: 14px;
            margin: 20px 0;

            background: #f4f6fa;
            border-radius: 8px;
        }

        .small {
            color: #64748b;
            font-size: 13px;
        }

    </style>

</head>

<body>

    <main>

        <h1>{$safeTitle}</h1>

        {$content}

    </main>

</body>
</html>
HTML;

    exit;
}


// =========================================================
// 4. HTTP-METHODE PRÜFEN
// =========================================================

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if (!in_array($method, ['GET', 'POST'], true)) {

    header('Allow: GET, POST');

    communityPage(
        'Anfrage nicht erlaubt',
        '<p>Diese Anfrage wird nicht unterstützt.</p>',
        405
    );
}


// =========================================================
// 5. TOKEN ENTGEGENNEHMEN
// =========================================================

// GET: Token kommt aus dem Bestätigungslink.
// POST: Token kommt aus dem versteckten Formularfeld.

$rawToken = $method === 'POST'
    ? ($_POST['token'] ?? null)
    : ($_GET['token'] ?? null);


if (
    !is_string($rawToken) ||
    !preg_match('/^[a-f0-9]{64}$/', $rawToken)
) {

    communityPage(
        'Link ungültig',
        '<p>Dieser Bestätigungslink ist nicht gültig.</p>',
        400
    );
}


// Nur den Hash mit der Datenbank vergleichen.

$tokenHash = hash('sha256', $rawToken);


// Zeitangaben einheitlich in UTC.

$now = new DateTimeImmutable(
    'now',
    new DateTimeZone('UTC')
);

$nowSql = $now->format('Y-m-d H:i:s');


// =========================================================
// 6. REGISTRIERUNG AUS DATENBANK LADEN
// =========================================================

try {

    $pdo = db($config);

    /*
     * Bei POST sperren wir den Datensatz innerhalb
     * einer Transaktion.
     *
     * Dadurch können beispielsweise zwei gleichzeitige
     * Klicks nicht denselben Vorgang parallel abschließen.
     */

    if ($method === 'POST') {
        $pdo->beginTransaction();
    }

    $sql = <<<SQL

SELECT
    id,
    firstname,
    lastname,
    email,
    newsletter_optin,
    created_at

FROM community_pending

WHERE
    token_hash = :token_hash

AND used_at IS NULL

AND expires_at > :now

LIMIT 1

SQL;

    if ($method === 'POST') {
        $sql .= ' FOR UPDATE';
    }

    $statement = $pdo->prepare($sql);

    $statement->execute([
        ':token_hash' => $tokenHash,
        ':now' => $nowSql
    ]);

    $registration = $statement->fetch();


    // =====================================================
    // 7. LINK ABGELAUFEN ODER BEREITS VERWENDET
    // =====================================================

    if (!$registration) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        communityPage(
            'Bestätigungslink nicht verfügbar',
            '<p>
                Dieser Link ist bereits verwendet worden
                oder seine Gültigkeit ist abgelaufen.
             </p>
             <p>
                Bitte starte den Communitybeitritt
                gegebenenfalls erneut.
             </p>',
            410
        );
    }


    $wantsNewsletter =
        (int)$registration['newsletter_optin'] === 1;


    // =====================================================
    // 8. GET – BESTÄTIGUNGSSEITE ANZEIGEN
    // =====================================================

    /*
     * Wichtig:
     * Bei einem einfachen GET-Aufruf wird weder
     * ein Moodle-Account erstellt noch eine
     * Newsletter-Anmeldung bestätigt.
     */

    if ($method === 'GET') {

        $safeFirstname = htmlspecialchars(
            $registration['firstname'],
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $safeToken = htmlspecialchars(
            $rawToken,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );


        // Newsletter wurde ursprünglich ausgewählt.

        if ($wantsNewsletter) {

            $newsletterInfo = <<<HTML

<div class="info">

    <strong>Deine Newsletter-Auswahl</strong>

    <p>
        Du hast zusätzlich unseren freiwilligen
        Newsletter mit Lerntipps, Neuigkeiten und
        Angeboten ausgewählt.
    </p>

    <p>
        Du kannst jetzt beides gemeinsam bestätigen
        oder ausschließlich der Community beitreten.
    </p>

</div>

HTML;

            $buttons = <<<HTML

<button
    type="submit"
    name="newsletter_action"
    value="confirm">

    Community und Newsletter bestätigen

</button>

<button
    type="submit"
    name="newsletter_action"
    value="skip"
    class="secondary">

    Nur Communitybeitritt bestätigen

</button>

HTML;

        } else {

            $newsletterInfo = '';

            $buttons = <<<HTML

<button type="submit">

    Communitybeitritt bestätigen

</button>

HTML;

        }


        // -------------------------------------------------
        // BESTÄTIGUNGSFORMULAR
        // -------------------------------------------------

        $content = <<<HTML

<p>Hallo {$safeFirstname},</p>

<p>
    schön, dass du dabei sein möchtest!
</p>

<p>
    Deine E-Mail-Adresse wurde über den
    Bestätigungslink aufgerufen.
</p>

<p>
    Bitte schließe deinen Communitybeitritt
    jetzt mit einem Klick ab.
</p>

{$newsletterInfo}

<form
    method="POST"
    action="/api/community-bestaetigen.php">

    <input
        type="hidden"
        name="token"
        value="{$safeToken}">

    {$buttons}

</form>

<p class="small">
    Erst nach deiner Bestätigung wird dein
    Communityzugang eingerichtet.
</p>

HTML;

        communityPage(
            'Communitybeitritt bestätigen',
            $content
        );
    }


    // =====================================================
    // 9. POST – NEWSLETTER-AUSWAHL PRÜFEN
    // =====================================================

    /*
     * Newsletter kann nur bestätigt werden,
     * wenn er bereits bei der ursprünglichen
     * Anmeldung freiwillig ausgewählt wurde.
     */

    $newsletterConfirmed =
        $wantsNewsletter
        &&
        ($_POST['newsletter_action'] ?? '') === 'confirm';


    // =====================================================
    // 10. MOODLE-ACCOUNT SUCHEN
    // =====================================================

    $email = $registration['email'];

    $existingUsers = moodleCall(
        'core_user_get_users_by_field',
        [
            'field' => 'email',
            'values[0]' => $email
        ],
        $config
    );

    if (!is_array($existingUsers)) {

        throw new RuntimeException(
            'Ungültige Antwort bei der Benutzersuche.'
        );
    }


    // =====================================================
    // 11. BESTEHENDEN ACCOUNT VERWENDEN
    // =====================================================

    if (count($existingUsers) > 0) {

        if (!isset($existingUsers[0]['id'])) {

            throw new RuntimeException(
                'Moodle hat keine gültige User-ID geliefert.'
            );
        }

        $userId = (int)$existingUsers[0]['id'];

    } else {


        // =================================================
        // 12. NEUEN ACCOUNT ERSTELLEN
        // =================================================

        $username = createUniqueUsername(
            $registration['firstname'],
            $registration['lastname'],
            $email,
            $config
        );


        $createdUsers = moodleCall(
            'core_user_create_users',
            [
                'users[0][username]' =>
                    $username,

                'users[0][firstname]' =>
                    $registration['firstname'],

                'users[0][lastname]' =>
                    $registration['lastname'],

                'users[0][email]' =>
                    $email,

                'users[0][auth]' =>
                    'manual',

                'users[0][createpassword]' =>
                    1
            ],
            $config
        );


        if (
            !is_array($createdUsers)
            ||
            !isset($createdUsers[0]['id'])
        ) {

            throw new RuntimeException(
                'Moodle-Account konnte nicht erstellt werden.'
            );
        }


        $userId = (int)$createdUsers[0]['id'];
    }


    // =====================================================
    // 13. COMMUNITY-KURS FREISCHALTEN
    // =====================================================

    moodleCall(
        'enrol_manual_enrol_users',
        [
            'enrolments[0][roleid]' =>
                (int)$config['role_id'],

            'enrolments[0][userid]' =>
                $userId,

            'enrolments[0][courseid]' =>
                (int)$config['course_id']
        ],
        $config
    );


    // =====================================================
    // 14. NEWSLETTER-EINWILLIGUNG DOKUMENTIEREN
    // =====================================================

    if ($newsletterConfirmed) {

        /*
         * Dieser Text muss dem Newsletter-Text
         * auf deiner Webseite entsprechen.
         */

        $consentText =
            'Ja, ich möchte zusätzlich den Newsletter '
            . 'von Gemeinsam den Meister meistern mit '
            . 'Lerntipps, Neuigkeiten und Angeboten '
            . 'per E-Mail erhalten. '
            . 'Ich kann mich jederzeit wieder abmelden.';


        $sql = <<<SQL

INSERT INTO community_newsletter
(
    firstname,
    email,
    requested_at,
    confirmed_at,
    consent_version,
    consent_text,
    unsubscribed_at
)

VALUES
(
    :firstname,
    :email,
    :requested_at,
    :confirmed_at,
    :consent_version,
    :consent_text,
    NULL
)

ON DUPLICATE KEY UPDATE

    firstname = VALUES(firstname),

    requested_at = VALUES(requested_at),

    confirmed_at = VALUES(confirmed_at),

    consent_version = VALUES(consent_version),

    consent_text = VALUES(consent_text),

    unsubscribed_at = NULL

SQL;


        $statement = $pdo->prepare($sql);

        $statement->execute([
            ':firstname' =>
                $registration['firstname'],

            ':email' =>
                $email,

            ':requested_at' =>
                $registration['created_at'],

            ':confirmed_at' =>
                $nowSql,

            ':consent_version' =>
                'community-newsletter-v1',

            ':consent_text' =>
                $consentText
        ]);
    }


    // =====================================================
    // 15. BESTÄTIGUNGSTOKEN ALS VERWENDET MARKIEREN
    // =====================================================

    $statement = $pdo->prepare(
        '
        UPDATE community_pending

        SET used_at = :used_at

        WHERE id = :id
        AND used_at IS NULL
        '
    );

    $statement->execute([
        ':used_at' => $nowSql,
        ':id' => $registration['id']
    ]);

    if ($statement->rowCount() !== 1) {

        throw new RuntimeException(
            'Bestätigung konnte nicht abgeschlossen werden.'
        );
    }


    // Änderungen in der Datenbank abschließen.

    $pdo->commit();


    // =====================================================
    // 16. ERFOLGSSEITE
    // =====================================================

    $newsletterMessage = $newsletterConfirmed
        ? '<p>Auch deine Newsletter-Anmeldung wurde bestätigt.</p>'
        : '';


    $content = <<<HTML

<p>
    Geschafft! Dein Communityzugang wurde
    erfolgreich eingerichtet.
</p>

{$newsletterMessage}

<p>
    Falls du noch keinen Moodle-Account hattest,
    erhältst du zusätzlich eine E-Mail zur
    Einrichtung deines Passworts.
</p>

<p>
    Wenn du bereits einen Account hast,
    kannst du dich direkt mit deinen
    vorhandenen Zugangsdaten anmelden.
</p>

<a
    class="button"
    href="https://lernen.gemeinsamdenmeistermeistern.de">

    Jetzt zur Lernplattform

</a>

HTML;


    communityPage(
        'Willkommen in der Community!',
        $content
    );


// =========================================================
// 17. FEHLERBEHANDLUNG
// =========================================================

} catch (Throwable $e) {

    /*
     * Falls die Verarbeitung abbricht, Änderungen an
     * unseren Datenbanktabellen zurücknehmen.
     *
     * Wurde der Moodle-Account zuvor bereits erstellt,
     * kann ein erneuter Versuch diesen wiederfinden.
     */

    if (
        isset($pdo)
        &&
        $pdo instanceof PDO
        &&
        $pdo->inTransaction()
    ) {

        $pdo->rollBack();
    }


    error_log(
        '[GDMM Community Bestätigung] '
        . $e->getMessage()
    );


    communityPage(
        'Das hat leider nicht geklappt',

        '<p>
            Dein Communitybeitritt konnte gerade
            nicht vollständig abgeschlossen werden.
         </p>

         <p>
            Bitte versuche es mit deinem
            Bestätigungslink erneut.
         </p>',

        500
    );
}
