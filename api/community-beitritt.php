<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GDMM – COMMUNITYBEITRITT
|--------------------------------------------------------------------------
|
| Erwartete POST-Felder:
|
| firstname
| lastname
| email
| newsletter_optin (optional: 0/1)
| website          (Honeypot, muss leer bleiben)
|
| Ablauf:
| 1. Eingaben prüfen
| 2. Newsletter-Auswahl übernehmen
| 3. Spam-Schutz durchführen
| 4. Bestätigungstoken erzeugen
| 5. Anmeldung vorläufig speichern
| 6. Bestätigungsmail verschicken
|
| WICHTIG:
| Hier wird noch kein Moodle-Account angelegt.
|
*/


// =========================================================
// 1. PRIVATE DATEIEN LADEN
// =========================================================

$privateDir = dirname(__DIR__, 2) . '/private';

require_once $privateDir . '/community-lib.php';

$config = require $privateDir . '/community-config.php';


// =========================================================
// 2. NUR POST ERLAUBEN
// =========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Allow: POST');

    jsonResponse(405, [
        'success' => false,
        'message' => 'Diese Anfrage ist nicht erlaubt.'
    ]);
}


// =========================================================
// 3. FORMULARDATEN LESEN
// =========================================================

$input = getRequestData();


// =========================================================
// 4. HONEYPOT PRÜFEN
// =========================================================

// Das Feld "website" darf nicht ausgefüllt sein.

$honeypot = $input['website'] ?? '';

if (
    !is_string($honeypot) ||
    trim($honeypot) !== ''
) {

    // Bot bekommt scheinbar eine Erfolgsantwort.
    // Tatsächlich wird nichts gespeichert oder versendet.

    successResponse();
}


// =========================================================
// 5. EINGABEN AUFBEREITEN
// =========================================================

$rawFirstname = $input['firstname'] ?? '';
$rawLastname  = $input['lastname'] ?? '';
$rawEmail     = $input['email'] ?? '';


// Datentypen prüfen.

if (
    !is_string($rawFirstname) ||
    !is_string($rawLastname) ||
    !is_string($rawEmail)
) {

    jsonResponse(422, [
        'success' => false,
        'message' => 'Bitte überprüfe deine Eingaben.'
    ]);
}


$firstname = trim($rawFirstname);

$lastname = trim($rawLastname);

$email = mb_strtolower(
    trim($rawEmail),
    'UTF-8'
);


// =========================================================
// 6. NEWSLETTER-EINWILLIGUNG
// =========================================================

/*
|--------------------------------------------------------------------------
| Standardmäßig ist der Newsletter NICHT aktiviert.
|
| Unterstützt:
| - JSON: true / false
| - HTML-Checkbox: on
| - Zahlen: 1 / 0
| - Zeichenketten: "1" / "0"
|
*/

$newsletterValue = $input['newsletter_optin'] ?? null;

$newsletterOptin = in_array(
    $newsletterValue,
    [
        true,
        1,
        '1',
        'true',
        'on',
        'yes'
    ],
    true
) ? 1 : 0;


// =========================================================
// 7. EINGABEN VALIDIEREN
// =========================================================

// Vorname

if (
    $firstname === '' ||
    mb_strlen($firstname) > 100 ||
    !mb_check_encoding($firstname, 'UTF-8') ||
    preg_match('/[\x00-\x1F\x7F]/u', $firstname)
) {

    jsonResponse(422, [
        'success' => false,
        'message' => 'Bitte gib einen gültigen Vornamen ein.'
    ]);
}


// Nachname

if (
    $lastname === '' ||
    mb_strlen($lastname) > 100 ||
    !mb_check_encoding($lastname, 'UTF-8') ||
    preg_match('/[\x00-\x1F\x7F]/u', $lastname)
) {

    jsonResponse(422, [
        'success' => false,
        'message' => 'Bitte gib einen gültigen Nachnamen ein.'
    ]);
}


// E-Mail-Adresse

if (
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {

    jsonResponse(422, [
        'success' => false,
        'message' => 'Bitte gib eine gültige E-Mail-Adresse ein.'
    ]);
}


// =========================================================
// 8. SPAM-SCHUTZ / RATE-LIMIT
// =========================================================

try {

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';


    // -----------------------------------------------------
    // Maximal 8 Versuche pro IP innerhalb von 15 Minuten.
    // -----------------------------------------------------

    if (!checkRateLimit(
        'ip:' . $ip,
        8,
        900,
        $config
    )) {

        jsonResponse(429, [
            'success' => false,
            'message' =>
                'Zu viele Versuche. Bitte versuche es später erneut.'
        ]);
    }


    // -----------------------------------------------------
    // Maximal 5 Versuche pro E-Mail innerhalb von 24 Stunden.
    // -----------------------------------------------------

    if (!checkRateLimit(
        'email:' . $email,
        5,
        86400,
        $config
    )) {

        jsonResponse(429, [
            'success' => false,
            'message' =>
                'Zu viele Versuche. Bitte versuche es später erneut.'
        ]);
    }


} catch (Throwable $e) {

    error_log(
        '[GDMM Rate-Limit] ' . $e->getMessage()
    );

    jsonResponse(500, [
        'success' => false,
        'message' =>
            'Der Communitybeitritt ist momentan nicht verfügbar.'
    ]);
}


// =========================================================
// 9. BESTÄTIGUNGSTOKEN ERZEUGEN
// =========================================================

try {

    // Kryptografisch zufälliger Token (64 Hex-Zeichen).

    $token = bin2hex(random_bytes(32));


    // Nur der Hash wird in der Datenbank gespeichert.

    $tokenHash = hash('sha256', $token);


    // -----------------------------------------------------
    // Zeitangaben einheitlich in UTC
    // -----------------------------------------------------

    $now = new DateTimeImmutable(
        'now',
        new DateTimeZone('UTC')
    );

    $expires = $now->modify(
        '+' . (int)$config['token_ttl_seconds'] . ' seconds'
    );

    $createdAt = $now->format('Y-m-d H:i:s');

    $expiresAt = $expires->format('Y-m-d H:i:s');


    // =====================================================
    // 10. ANMELDUNG IN DATENBANK SPEICHERN
    // =====================================================

    $pdo = db($config);

    /*
     * Existiert die E-Mail-Adresse bereits in
     * community_pending, wird der bisherige
     * Bestätigungsvorgang ersetzt.
     *
     * Das bedeutet:
     * Ein neuer Bestätigungslink macht den alten ungültig.
     */

    $sql = <<<SQL

INSERT INTO community_pending
(
    firstname,
    lastname,
    email,
    newsletter_optin,
    token_hash,
    created_at,
    expires_at,
    used_at
)

VALUES
(
    :firstname,
    :lastname,
    :email,
    :newsletter_optin,
    :token_hash,
    :created_at,
    :expires_at,
    NULL
)

ON DUPLICATE KEY UPDATE

    firstname = VALUES(firstname),

    lastname = VALUES(lastname),

    newsletter_optin = VALUES(newsletter_optin),

    token_hash = VALUES(token_hash),

    created_at = VALUES(created_at),

    expires_at = VALUES(expires_at),

    used_at = NULL

SQL;


    $statement = $pdo->prepare($sql);


    $statement->execute([
        ':firstname' => $firstname,
        ':lastname' => $lastname,
        ':email' => $email,

        ':newsletter_optin' => $newsletterOptin,

        ':token_hash' => $tokenHash,
        ':created_at' => $createdAt,
        ':expires_at' => $expiresAt
    ]);


    // =====================================================
    // 11. BESTÄTIGUNGSMAIL VERSENDEN
    // =====================================================

    /*
     * Der letzte Parameter übergibt die Newsletter-Auswahl
     * an unsere neue Funktion in community-lib.php.
     *
     * 0 = Nur Communitybeitritt
     * 1 = Communitybeitritt + Newsletter
     */

    $mailSent = sendConfirmationMail(
        $email,
        $firstname,
        $token,
        $config,
        $newsletterOptin
    );


    if (!$mailSent) {

        throw new RuntimeException(
            'Bestätigungsmail konnte nicht versendet werden.'
        );
    }


    // =====================================================
    // 12. ERFOLGSANTWORT
    // =====================================================

    successResponse();


} catch (Throwable $e) {

    // Fehler nur serverseitig protokollieren.
    // Keine Zugangsdaten an den Browser zurückgeben.

    error_log(
        '[GDMM Communitybeitritt] ' . $e->getMessage()
    );


    jsonResponse(500, [
        'success' => false,
        'message' =>
            'Der Communitybeitritt konnte gerade nicht abgeschlossen werden. Bitte versuche es später erneut.'
    ]);
}
