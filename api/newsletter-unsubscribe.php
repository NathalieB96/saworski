<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GDMM – NEWSLETTER ABMELDEN
|--------------------------------------------------------------------------
|
| GET:
| Zeigt die Abmeldeseite an. Es wird nichts gelöscht.
|
| POST:
| Überprüft den persönlichen Abmeldelink und löscht anschließend
| den entsprechenden Eintrag aus community_newsletter.
|
| Der Moodle-Account und die Community bleiben unberührt.
|
*/


// =========================================================
// 1. SICHERHEITSHEADER
// =========================================================

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');


// =========================================================
// 2. PRIVATE DATEIEN LADEN
// =========================================================

$privateDir = dirname(__DIR__, 2) . '/private';

require_once $privateDir . '/community-lib.php';
require_once $privateDir . '/newsletter-unsubscribe-lib.php';

$config = require $privateDir . '/community-config.php';


// =========================================================
// 3. AUSGABEFUNKTION
// =========================================================

function showUnsubscribePage(
    string $title,
    string $message,
    bool $showForm = false,
    int $subscriberId = 0,
    string $token = ''
): never {

    $title = htmlspecialchars(
        $title,
        ENT_QUOTES,
        'UTF-8'
    );

    $message = htmlspecialchars(
        $message,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeToken = htmlspecialchars(
        $token,
        ENT_QUOTES,
        'UTF-8'
    );

    $form = '';

    if ($showForm) {

        $form = <<<HTML

        <form method="post" action="newsletter-unsubscribe.php">

            <input
                type="hidden"
                name="id"
                value="{$subscriberId}"
            >

            <input
                type="hidden"
                name="token"
                value="{$safeToken}"
            >

            <button type="submit">
                Newsletter abmelden
            </button>

        </form>

        <a class="secondary" href="https://gemeinsamdenmeistermeistern.de/">
            Abbrechen
        </a>

        HTML;

    } else {

        $form = <<<HTML

        <a class="home-button" href="https://gemeinsamdenmeistermeistern.de/">
            Zur Webseite
        </a>

        HTML;
    }

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta name="robots" content="noindex, nofollow">

        <title>{$title} – Gemeinsam den Meister meistern</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 24px;

                background: #f3f6fa;
                color: #1f2937;

                font-family: Arial, Helvetica, sans-serif;
                line-height: 1.6;
            }

            .card {
                width: 100%;
                max-width: 480px;

                padding: 36px 28px;

                background: #ffffff;
                border-radius: 16px;

                text-align: center;

                box-shadow:
                    0 8px 30px rgba(0, 0, 0, 0.06);
            }

            h1 {
                margin: 0 0 16px;

                color: #1565c0;
                font-size: 26px;
                line-height: 1.3;
            }

            p {
                margin: 0 0 24px;
                font-size: 16px;
            }

            button,
            .home-button {
                display: block;
                width: 100%;

                padding: 14px 20px;

                border: 0;
                border-radius: 8px;

                background: #2e7d32;
                color: #ffffff;

                font-size: 16px;
                font-weight: bold;
                text-decoration: none;

                cursor: pointer;
            }

            button:hover,
            .home-button:hover {
                background: #256628;
            }

            .secondary {
                display: inline-block;
                margin-top: 20px;

                color: #546e7a;
                text-decoration: none;
            }

            .secondary:hover {
                text-decoration: underline;
            }

        </style>

    </head>

    <body>

        <main class="card">

            <h1>{$title}</h1>

            <p>{$message}</p>

            {$form}

        </main>

    </body>

    </html>
    HTML;

    exit;
}


// =========================================================
// 4. NUR GET UND POST ZULASSEN
// =========================================================

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if (!in_array($method, ['GET', 'POST'], true)) {

    header('Allow: GET, POST', true, 405);

    showUnsubscribePage(
        'Ungültiger Aufruf',
        'Diese Anfrage wird nicht unterstützt.'
    );
}


// =========================================================
// 5. PARAMETER LESEN UND PRÜFEN
// =========================================================

$input = $method === 'POST' ? $_POST : $_GET;

$rawId = $input['id'] ?? '';
$rawToken = $input['token'] ?? '';

$id = filter_var(
    $rawId,
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);

$token = is_string($rawToken)
    ? strtolower($rawToken)
    : '';

if (
    $id === false
    || !preg_match('/\A[a-f0-9]{64}\z/D', $token)
) {

    http_response_code(400);

    showUnsubscribePage(
        'Ungültiger Abmeldelink',
        'Der Link ist ungültig oder wurde bereits verändert.'
    );
}


// =========================================================
// 6. DATENBANK UND TOKEN ÜBERPRÜFEN
// =========================================================

try {

    $pdo = db($config);

    // Bei POST sperren wir den Datensatz während der Prüfung
    // und anschließenden Löschung.

    if ($method === 'POST') {
        $pdo->beginTransaction();
    }

    $sql = <<<SQL

        SELECT
            id,
            firstname,
            email,
            confirmed_at

        FROM community_newsletter

        WHERE id = :id

        LIMIT 1

    SQL;

    if ($method === 'POST') {
        $sql .= ' FOR UPDATE';
    }

    $statement = $pdo->prepare($sql);

    $statement->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $statement->execute();

    $subscriber = $statement->fetch(PDO::FETCH_ASSOC);


    // =====================================================
    // 7. LINK EXISTIERT NICHT MEHR
    // =====================================================

    if (!$subscriber) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        showUnsubscribePage(
            'Keine aktive Anmeldung gefunden',
            'Dieser Abmeldelink ist nicht mehr gültig. Möglicherweise wurde der Newsletter bereits abbestellt.'
        );
    }


    // =====================================================
    // 8. SIGNATUR KONTROLLIEREN
    // =====================================================

    if (
        !newsletterUnsubscribeTokenValid(
            $subscriber,
            $config,
            $token
        )
    ) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(400);

        showUnsubscribePage(
            'Ungültiger Abmeldelink',
            'Der persönliche Abmeldelink konnte nicht überprüft werden.'
        );
    }


    // =====================================================
    // 9. GET – NUR BESTÄTIGUNGSSEITE ANZEIGEN
    // =====================================================

    if ($method === 'GET') {

        showUnsubscribePage(
            'Newsletter abmelden',
            'Möchtest du unseren Newsletter wirklich abbestellen? Dein Communityzugang bleibt selbstverständlich bestehen.',
            true,
            (int)$subscriber['id'],
            $token
        );
    }


    // =====================================================
    // 10. POST – NEWSLETTER-DATENSATZ LÖSCHEN
    // =====================================================

    $delete = $pdo->prepare(
        'DELETE FROM community_newsletter WHERE id = :id'
    );

    $delete->bindValue(
        ':id',
        $id,
        PDO::PARAM_INT
    );

    $delete->execute();

    if ($delete->rowCount() !== 1) {
        throw new RuntimeException(
            'Newsletter-Datensatz konnte nicht geloescht werden.'
        );
    }

    $pdo->commit();


    // =====================================================
    // 11. ERFOLGSMELDUNG
    // =====================================================

    showUnsubscribePage(
        'Erfolgreich abgemeldet',
        'Du wurdest aus unserem Newsletter-Verteiler entfernt. Dein Communityzugang bleibt weiterhin bestehen. Vielleicht lesen wir uns ja irgendwann wieder!'
    );


// =========================================================
// 12. FEHLERBEHANDLUNG
// =========================================================

} catch (Throwable $e) {

    if (
        isset($pdo)
        && $pdo instanceof PDO
        && $pdo->inTransaction()
    ) {
        $pdo->rollBack();
    }

    error_log(
        '[GDMM Newsletter Unsubscribe] ' . $e->getMessage()
    );

    http_response_code(500);

    showUnsubscribePage(
        'Das hat leider nicht geklappt',
        'Bei der Abmeldung ist ein technischer Fehler aufgetreten. Bitte versuche es später erneut.'
    );
}
