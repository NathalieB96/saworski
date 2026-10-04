<?php
declare(strict_types=1);

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$baseDirectory = dirname(__DIR__, 2);

require_once $baseDirectory . '/private/community-lib.php';
require_once $baseDirectory . '/private/newsletter/newsletter-renderer.php';
require_once $baseDirectory . '/private/newsletter/newsletter-admin-auth.php';
require_once $baseDirectory . '/private/newsletter/newsletter-mailer.php';
require_once $baseDirectory . '/private/newsletter/newsletter-campaigns.php';

$config = require $baseDirectory . '/private/community-config.php';

newsletterAdminStartSession();


function adminEscape(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function newsletterAdminFieldLabel(
    string $field
): string {

    return [

        'BETREFF' =>
            'Betreff',

        'PREHEADER' =>
            'Vorschautext im Postfach',

        'KATEGORIE' =>
            'Kategorie',

        'UEBERSCHRIFT' =>
            'Überschrift',

        'INHALT' =>
            'Inhalt',

        'TEXT' =>
            'Kurzer Text',

        'BUTTON_TEXT' =>
            'Buttontext',

        'BUTTON_URL' =>
            'Button-Link',

        'KURS_1_WOCHENTAG' =>
            'Wochentag',

        'KURS_1_RHYTHMUS' =>
            'Rhythmus',

        'KURS_1_BADGE' =>
            'Zeitmodell',

        'KURS_1_ZEIT_1' =>
            'Uhrzeit 1',

        'KURS_1_ZEIT_2' =>
            'Uhrzeit 2 · optional',

        'KURS_1_START' =>
            'Startdatum',

        'KURS_1_ENDE' =>
            'Enddatum',

        'KURS_1_PREIS' =>
            'Preis',

        'KURS_1_PREIS_ALT' =>
            'Alter Preis',

        'KURS_1_URL' =>
            'Link zur Kurskachel',

        'KURS_2_WOCHENTAG' =>
            'Wochentag',

        'KURS_2_RHYTHMUS' =>
            'Rhythmus',

        'KURS_2_BADGE' =>
            'Zeitmodell',

        'KURS_2_ZEIT_1' =>
            'Uhrzeit 1',

        'KURS_2_ZEIT_2' =>
            'Uhrzeit 2 · optional',

        'KURS_2_START' =>
            'Startdatum',

        'KURS_2_ENDE' =>
            'Enddatum',

        'KURS_2_PREIS' =>
            'Preis',

        'KURS_2_PREIS_ALT' =>
            'Alter Preis',

        'KURS_2_URL' =>
            'Link zur Kurskachel',

        'BUCHUNG_URL' =>
            'Allgemeine Buchungsseite',

    ][$field]
    ??
    mb_convert_case(
        str_replace(
            '_',
            ' ',
            $field
        ),
        MB_CASE_TITLE,
        'UTF-8'
    );
}


function newsletterAdminFieldPlaceholder(
    string $field
): string {

    return [

        'BETREFF' =>
            'z. B. Noch Plätze frei: NTG-Begleitkurs',

        'PREHEADER' =>
            'Kurzer Vorschautext für das E-Mail-Postfach',

        'KATEGORIE' =>
            'z. B. NOCH PLÄTZE FREI',

        'UEBERSCHRIFT' =>
            'z. B. Dein passender NTG-Kurs',

        'TEXT' =>
            'Ein bis zwei kurze Sätze …',

        'BUTTON_TEXT' =>
            'z. B. Jetzt trainieren',

        'BUTTON_URL' =>
            'https://...',

        'KURS_1_START' =>
            'TT.MM.JJJJ',

        'KURS_1_ENDE' =>
            'TT.MM.JJJJ',

        'KURS_1_PREIS' =>
            '890 €',

        'KURS_1_PREIS_ALT' =>
            '980 €',

        'KURS_1_URL' =>
            'https://...#FWW-27-1',

        'KURS_2_START' =>
            'TT.MM.JJJJ',

        'KURS_2_ENDE' =>
            'TT.MM.JJJJ',

        'KURS_2_PREIS' =>
            '890 €',

        'KURS_2_PREIS_ALT' =>
            '980 €',

        'KURS_2_URL' =>
            'https://...#FAW-27-2',

        'BUCHUNG_URL' =>
            'https://.../kurse',

    ][$field]
    ?? '';
}


function newsletterAdminIsUrl(
    string $field
): bool {

    return str_ends_with(
        $field,
        '_URL'
    );
}


function newsletterAdminIsCourseField(
    string $field
): bool {

    return
        str_starts_with(
            $field,
            'KURS_1_'
        )
        ||
        str_starts_with(
            $field,
            'KURS_2_'
        );
}


function newsletterAdminIsValidUrl(
    string $value
): bool {

    if (
        !filter_var(
            $value,
            FILTER_VALIDATE_URL
        )
    ) {
        return false;
    }

    $scheme =
        strtolower(
            (string)parse_url(
                $value,
                PHP_URL_SCHEME
            )
        );

    return in_array(
        $scheme,
        [
            'http',
            'https',
        ],
        true
    );
}


function newsletterAdminIsValidDate(
    string $value
): bool {

    $value =
        trim(
            $value
        );

    $date =
        DateTimeImmutable::createFromFormat(
            '!d.m.Y',
            $value
        );

    $errors =
        DateTimeImmutable::getLastErrors();

    if (
        $date === false
    ) {
        return false;
    }

    if (
        $errors !== false
        &&
        (
            ($errors['warning_count'] ?? 0) > 0
            ||
            ($errors['error_count'] ?? 0) > 0
        )
    ) {
        return false;
    }

    return
        $date->format(
            'd.m.Y'
        )
        ===
        $value;
}



function newsletterAdminCourseDateToInput(
    string $value
): string {

    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $iso = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $isoErrors = DateTimeImmutable::getLastErrors();

    if (
        $iso !== false
        &&
        (
            $isoErrors === false
            ||
            (
                ($isoErrors['warning_count'] ?? 0) === 0
                &&
                ($isoErrors['error_count'] ?? 0) === 0
            )
        )
        &&
        $iso->format('Y-m-d') === $value
    ) {
        return $value;
    }

    $german = DateTimeImmutable::createFromFormat('!d.m.Y', $value);
    $germanErrors = DateTimeImmutable::getLastErrors();

    if (
        $german !== false
        &&
        (
            $germanErrors === false
            ||
            (
                ($germanErrors['warning_count'] ?? 0) === 0
                &&
                ($germanErrors['error_count'] ?? 0) === 0
            )
        )
        &&
        $german->format('d.m.Y') === $value
    ) {
        return $german->format('Y-m-d');
    }

    return '';
}


function newsletterAdminCourseDateToDisplay(
    string $value
): string {

    $value = trim($value);

    if ($value === '') {
        return '';
    }

    $iso = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $isoErrors = DateTimeImmutable::getLastErrors();

    if (
        $iso !== false
        &&
        (
            $isoErrors === false
            ||
            (
                ($isoErrors['warning_count'] ?? 0) === 0
                &&
                ($isoErrors['error_count'] ?? 0) === 0
            )
        )
        &&
        $iso->format('Y-m-d') === $value
    ) {
        return $iso->format('d.m.Y');
    }

    return $value;
}


function newsletterAdminIsValidTime(
    string $value
): bool {

    return
        preg_match(
            '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
            trim(
                $value
            )
        )
        ===
        1;
}


function newsletterAdminSplitTimeRange(
    string $value
): array {

    if (
        preg_match(
            '/(\d{1,2}:\d{2}).*?(\d{1,2}:\d{2})/u',
            $value,
            $matches
        )
    ) {

        return [

            sprintf(
                '%02d:%02d',
                (int)substr(
                    $matches[1],
                    0,
                    2
                ),
                (int)substr(
                    $matches[1],
                    -2
                )
            ),

            sprintf(
                '%02d:%02d',
                (int)substr(
                    $matches[2],
                    0,
                    2
                ),
                (int)substr(
                    $matches[2],
                    -2
                )
            ),
        ];
    }

    return [
        '',
        '',
    ];
}


function newsletterAdminComposeTimeRangeSoft(
    string $start,
    string $end
): string {

    $start =
        trim(
            $start
        );

    $end =
        trim(
            $end
        );

    return
        (
            $start !== ''
            &&
            $end !== ''
        )
        ?
        $start
        . '–'
        . $end
        . ' Uhr'
        :
        '';
}


function newsletterAdminTimeOptions(): array
{
    $options = [];

    for (
        $hour = 0;
        $hour < 24;
        $hour++
    ) {

        foreach (
            [
                0,
                15,
                30,
                45,
            ]
            as
            $minute
        ) {

            $options[] =
                sprintf(
                    '%02d:%02d',
                    $hour,
                    $minute
                );
        }
    }

    return $options;
}


function newsletterAdminResolveChoice(
    string $choice,
    string $custom
): string {

    return
        $choice === '__custom__'
        ?
        trim(
            $custom
        )
        :
        trim(
            $choice
        );
}


function newsletterAdminChoiceUi(
    string $value,
    array $standardOptions
): array {

    $value =
        trim(
            $value
        );

    if (
        $value === ''
    ) {

        return [
            'choice' => '',
            'custom' => '',
        ];
    }

    if (
        in_array(
            $value,
            $standardOptions,
            true
        )
    ) {

        return [
            'choice' => $value,
            'custom' => '',
        ];
    }

    return [
        'choice' => '__custom__',
        'custom' => $value,
    ];
}


function newsletterAdminResolveScheduleUtc(
    string $mode,
    string $date,
    string $time
): ?string {

    if (
        $mode !== 'scheduled'
    ) {
        return null;
    }

    if (
        $date === ''
        ||
        $time === ''
    ) {

        throw new RuntimeException(
            'Bitte wähle Datum und Uhrzeit für den Versand.'
        );
    }

    $timezone =
        new DateTimeZone(
            'Europe/Berlin'
        );

    $dateTime =
        DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $date
            . ' '
            . $time,
            $timezone
        );

    $errors =
        DateTimeImmutable::getLastErrors();

    if (
        $dateTime === false
        ||
        (
            $errors !== false
            &&
            (
                ($errors['warning_count'] ?? 0) > 0
                ||
                ($errors['error_count'] ?? 0) > 0
            )
        )
    ) {

        throw new RuntimeException(
            'Der gewählte Versandzeitpunkt ist ungültig.'
        );
    }

    $now =
        new DateTimeImmutable(
            'now',
            $timezone
        );

    if (
        $dateTime
        <
        $now->modify(
            '-1 minute'
        )
    ) {

        throw new RuntimeException(
            'Der gewählte Versandzeitpunkt liegt in der Vergangenheit.'
        );
    }

    return
        $dateTime
        ->setTimezone(
            new DateTimeZone(
                'UTC'
            )
        )
        ->format(
            'Y-m-d H:i:s'
        );
}


function newsletterAdminFormatUtc(
    ?string $value
): string {

    if (
        $value === null
        ||
        trim(
            $value
        )
        === ''
    ) {

        return
            'nächstmöglich';
    }

    return
        (
            new DateTimeImmutable(
                $value,
                new DateTimeZone(
                    'UTC'
                )
            )
        )
        ->setTimezone(
            new DateTimeZone(
                'Europe/Berlin'
            )
        )
        ->format(
            'd.m.Y H:i'
        );
}


function newsletterAdminStatusLabel(
    string $status
): string {

    return match (
        $status
    ) {

        'queued' =>
            'geplant',

        'sending' =>
            'wird versendet',

        'completed' =>
            'abgeschlossen',

        'cancelled' =>
            'abgebrochen',

        default =>
            $status,
    };
}


function newsletterAdminHasCourse2Data(
    array $values,
    array $timeParts
): bool {

    foreach (
        $values
        as
        $key
        =>
        $value
    ) {

        if (
            str_starts_with(
                (string)$key,
                'KURS_2_'
            )
            &&
            trim(
                (string)$value
            )
            !== ''
        ) {

            return true;
        }
    }

    foreach (
        [
            'KURS_2_ZEIT_1',
            'KURS_2_ZEIT_2',
        ]
        as
        $field
    ) {

        if (
            trim(
                (string)(
                    $timeParts[$field]['start']
                    ?? ''
                )
            )
            !== ''
            ||
            trim(
                (string)(
                    $timeParts[$field]['end']
                    ?? ''
                )
            )
            !== ''
        ) {

            return true;
        }
    }

    return false;
}


function newsletterAdminValidateCurrentTemplate(
    array $editableFields,
    array $values,
    array $timeParts
): array {

    $errors = [];

    $weekdays = [
        'Montag',
        'Dienstag',
        'Mittwoch',
        'Donnerstag',
        'Freitag',
        'Samstag',
        'Sonntag',
    ];

    $badges = [
        'Früh/Spät',
        'Spät',
        'Früh',
    ];

    $course2Used =
        newsletterAdminHasCourse2Data(
            $values,
            $timeParts
        );


    /*
    |--------------------------------------------------------------------------
    | NORMALE FELDER DER AKTUELLEN VORLAGE
    |--------------------------------------------------------------------------
    */

    foreach (
        $editableFields
        as
        $field
    ) {

        if (
            newsletterAdminIsCourseField(
                $field
            )
        ) {
            continue;
        }

        $value =
            trim(
                (string)(
                    $values[$field]
                    ?? ''
                )
            );

        $empty =
            $field === 'INHALT'
            ?
            trim(
                html_entity_decode(
                    strip_tags(
                        str_replace(
                            [
                                '<br>',
                                '<br/>',
                                '<br />',
                            ],
                            ' ',
                            (string)(
                                $values[$field]
                                ?? ''
                            )
                        )
                    ),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
            )
            === ''
            :
            $value === '';


        if (
            $empty
        ) {

            $errors[$field] =
                'Bitte fülle dieses Feld aus.';

            continue;
        }


        if (
            newsletterAdminIsUrl(
                $field
            )
            &&
            !newsletterAdminIsValidUrl(
                $value
            )
        ) {

            $errors[$field] =
                'Bitte gib eine vollständige URL mit http:// oder https:// ein.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | KURSVORLAGE
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            'KURS_1_WOCHENTAG',
            $editableFields,
            true
        )
    ) {

        foreach (
            [
                1,
                2,
            ]
            as
            $number
        ) {

            if (
                $number === 2
                &&
                !$course2Used
            ) {
                continue;
            }

            $prefix =
                'KURS_'
                . $number
                . '_';


            $required = [
                'WOCHENTAG',
                'RHYTHMUS',
                'BADGE',
                'ZEIT_1',
                'START',
                'ENDE',
                'PREIS',
                'URL',
            ];


            foreach (
                $required
                as
                $suffix
            ) {

                $field =
                    $prefix
                    . $suffix;

                if (
                    trim(
                        (string)(
                            $values[$field]
                            ?? ''
                        )
                    )
                    === ''
                ) {

                    $errors[$field] =
                        'Bitte fülle dieses Feld aus.';
                }
            }


            /*
            | Wochentag
            */

            $weekdayField =
                $prefix
                . 'WOCHENTAG';

            if (
                ($values[$weekdayField] ?? '')
                !== ''
                &&
                !in_array(
                    (string)$values[$weekdayField],
                    $weekdays,
                    true
                )
            ) {

                $errors[$weekdayField] =
                    'Bitte wähle einen gültigen Wochentag.';
            }


            /*
            | Zeitmodell
            */

            $badgeField =
                $prefix
                . 'BADGE';

            if (
                ($values[$badgeField] ?? '')
                !== ''
                &&
                !in_array(
                    (string)$values[$badgeField],
                    $badges,
                    true
                )
            ) {

                $errors[$badgeField] =
                    'Bitte wähle ein gültiges Zeitmodell.';
            }


            /*
            | Datum
            */

            foreach (
                [
                    'START',
                    'ENDE',
                ]
                as
                $dateSuffix
            ) {

                $dateField =
                    $prefix
                    . $dateSuffix;

                $dateValue =
                    trim(
                        (string)(
                            $values[$dateField]
                            ?? ''
                        )
                    );

                if (
                    $dateValue !== ''
                    &&
                    !newsletterAdminIsValidDate(
                        $dateValue
                    )
                ) {

                    $errors[$dateField] =
                        'Bitte gib das Datum als TT.MM.JJJJ ein.';
                }
            }


            /*
            | Uhrzeiten
            */

            foreach (
                [
                    'ZEIT_1',
                    'ZEIT_2',
                ]
                as
                $timeSuffix
            ) {

                $field =
                    $prefix
                    . $timeSuffix;

                $start =
                    trim(
                        (string)(
                            $timeParts[$field]['start']
                            ?? ''
                        )
                    );

                $end =
                    trim(
                        (string)(
                            $timeParts[$field]['end']
                            ?? ''
                        )
                    );

                $isRequired =
                    $timeSuffix
                    ===
                    'ZEIT_1';


                if (
                    $isRequired
                    &&
                    (
                        $start === ''
                        ||
                        $end === ''
                    )
                ) {

                    $errors[$field] =
                        'Bitte wähle Start- und Endzeit.';

                    continue;
                }


                if (
                    (
                        $start !== ''
                        ||
                        $end !== ''
                    )
                    &&
                    (
                        $start === ''
                        ||
                        $end === ''
                    )
                ) {

                    $errors[$field] =
                        'Bitte gib Start- und Endzeit vollständig an.';

                    continue;
                }


                if (
                    $start !== ''
                    &&
                    !newsletterAdminIsValidTime(
                        $start
                    )
                ) {

                    $errors[$field] =
                        'Die Startzeit ist ungültig.';
                }


                if (
                    $end !== ''
                    &&
                    !newsletterAdminIsValidTime(
                        $end
                    )
                ) {

                    $errors[$field] =
                        'Die Endzeit ist ungültig.';
                }
            }


            /*
            | Kurs-Link
            */

            $urlField =
                $prefix
                . 'URL';

            $urlValue =
                trim(
                    (string)(
                        $values[$urlField]
                        ?? ''
                    )
                );

            if (
                $urlValue !== ''
                &&
                !newsletterAdminIsValidUrl(
                    $urlValue
                )
            ) {

                $errors[$urlField] =
                    'Bitte gib eine vollständige URL mit http:// oder https:// ein.';
            }
        }
    }

    return $errors;
}


function newsletterAdminFieldClass(
    string $field,
    array $errors
): string {

    return
        isset(
            $errors[$field]
        )
        ?
        'field has-error'
        :
        'field';
}


function renderLoginPage(
    ?string $error = null
): never {

    ?>

    <!DOCTYPE html>
    <html lang="de">

    <head>

        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="robots"
            content="noindex,nofollow,noarchive"
        >

        <title>
            GDMM Newsletter – Login
        </title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                background: #f3f5f8;
                color: #161c29;
                font-family: Arial, Helvetica, sans-serif;
            }

            .login {
                width: min(
                    430px,
                    calc(100% - 32px)
                );
                margin: 10vh auto;
                background: #fff;
                border: 1px solid #e5e9f0;
                border-radius: 18px;
                padding: 30px;
                box-shadow:
                    0 7px 24px
                    rgba(20,32,55,.06);
            }

            .brand {
                color: #0043FA;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 1.2px;
                margin-bottom: 7px;
            }

            .login h1 {
                margin: 0 0 8px;
                font-size: 28px;
            }

            .login p {
                margin: 0 0 22px;
                color: #667085;
                line-height: 1.5;
            }

            .login label {
                display: block;
                margin-bottom: 7px;
                font-weight: 700;
                font-size: 13px;
            }

            .login input {
                width: 100%;
                height: 46px;
                border: 1px solid #ccd4df;
                border-radius: 10px;
                padding: 0 12px;
                font-size: 16px;
                margin-bottom: 16px;
            }

            .login button {
                width: 100%;
                height: 48px;
                border: 0;
                border-radius: 10px;
                background: #ACF20D;
                font-size: 15px;
                font-weight: 800;
                cursor: pointer;
            }

            .error {
                margin-bottom: 18px;
                padding: 12px 14px;
                border: 1px solid #f0b7b7;
                border-radius: 10px;
                background: #fff3f3;
                color: #8a1f1f;
                font-size: 14px;
            }

        </style>

    </head>

    <body>

    <main class="login">

        <div class="brand">
            GDMM INTERN
        </div>

        <h1>
            Newsletter
        </h1>

        <p>
            Interner Bereich für Vorschau und Versand.
        </p>

        <?php if (
            $error !== null
        ): ?>

            <div class="error">
                <?= adminEscape(
                    $error
                ) ?>
            </div>

        <?php endif; ?>

        <form method="post">

            <input
                type="hidden"
                name="action"
                value="login"
            >

            <label for="admin_password">
                Passwort
            </label>

            <input
                type="password"
                id="admin_password"
                name="admin_password"
                autocomplete="current-password"
                required
                autofocus
            >

            <button type="submit">
                Anmelden
            </button>

        </form>

    </main>

    </body>

    </html>

    <?php

    exit;
}


$action =
    (string)(
        $_POST['action']
        ?? ''
    );


if (
    !newsletterAdminIsAuthenticated()
) {

    if (
        $_SERVER['REQUEST_METHOD']
        ===
        'POST'
        &&
        $action
        ===
        'login'
    ) {

        try {

            if (
                newsletterAdminLogin(
                    (string)(
                        $_POST['admin_password']
                        ?? ''
                    ),
                    $config
                )
            ) {

                header(
                    'Location: /intern/newsletter.php'
                );

                exit;
            }


            renderLoginPage(
                'Passwort ist nicht korrekt.'
            );


        } catch (
            Throwable $exception
        ) {

            renderLoginPage(
                $exception->getMessage()
            );
        }
    }


    renderLoginPage();
}


if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
    &&
    $action
    ===
    'logout'
) {

    newsletterAdminVerifyCsrf(
        (string)(
            $_POST['csrf']
            ?? ''
        )
    );

    newsletterAdminLogout();

    header(
        'Location: /intern/newsletter.php'
    );

    exit;
}


$csrf =
    newsletterAdminCsrfToken();

$pdo =
    db(
        $config
    );


/*
|--------------------------------------------------------------------------
| STATUS JSON
|--------------------------------------------------------------------------
*/

if (
    isset(
        $_GET['status_json']
    )
    &&
    $_GET['status_json']
    ===
    '1'
) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    try {

        $campaigns =
            newsletterRecentCampaigns(
                $pdo,
                10
            );

        $subscriberCountJson =
            newsletterSubscriberCount(
                $pdo
            );


        foreach (
            $campaigns
            as
            &$campaign
        ) {

            $campaign['status_label'] =
                newsletterAdminStatusLabel(
                    (string)$campaign['status']
                );

            $campaign['scheduled_label'] =
                newsletterAdminFormatUtc(
                    $campaign['scheduled_at']
                    !== null
                    ?
                    (string)$campaign['scheduled_at']
                    :
                    null
                );

            $campaign['open_count'] =
                max(
                    0,
                    (int)$campaign['total_count']
                    -
                    (int)$campaign['sent_count']
                    -
                    (int)$campaign['skipped_count']
                    -
                    (int)$campaign['failed_count']
                    -
                    (int)$campaign['cancelled_count']
                );
        }

        unset(
            $campaign
        );


        echo json_encode(
            [
                'success' =>
                    true,

                'subscriber_count' =>
                    $subscriberCountJson,

                'campaigns' =>
                    $campaigns,
            ],
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
            |
            JSON_THROW_ON_ERROR
        );


    } catch (
        Throwable $exception
    ) {

        http_response_code(
            500
        );

        echo json_encode(
            [
                'success' =>
                    false,

                'message' =>
                    $exception->getMessage(),
            ],
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        );
    }

    exit;
}


/*
|--------------------------------------------------------------------------
| BASISDATEN
|--------------------------------------------------------------------------
*/

$error = null;
$success = null;
$previewHtml = null;
$prepareSend = false;
$fieldErrors = [];
$templates = [];
$subscriberCount = 0;


try {

    $templates =
        newsletterTemplates();

    $subscriberCount =
        newsletterSubscriberCount(
            $pdo
        );


} catch (
    Throwable $exception
) {

    $error =
        $exception->getMessage();
}


$selectedTemplate = '';


if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
) {

    $selectedTemplate =
        trim(
            (string)(
                $_POST['template']
                ?? ''
            )
        );


} elseif (
    isset(
        $_GET['template']
    )
) {

    $selectedTemplate =
        trim(
            (string)$_GET['template']
        );
}


if (
    $selectedTemplate === ''
    &&
    !empty(
        $templates
    )
) {

    $firstTemplate =
        reset(
            $templates
        );

    if (
        is_array(
            $firstTemplate
        )
    ) {

        $selectedTemplate =
            (string)$firstTemplate['key'];
    }
}


if (
    $selectedTemplate !== ''
    &&
    !isset(
        $templates[$selectedTemplate]
    )
) {

    $error =
        'Die ausgewählte Vorlage existiert nicht.';

    $selectedTemplate =
        '';
}


$templateFields = [];


if (
    $selectedTemplate !== ''
    &&
    $error === null
) {

    try {

        $templateFields =
            newsletterTemplateFields(
                $selectedTemplate
            );


    } catch (
        Throwable $exception
    ) {

        $error =
            $exception->getMessage();
    }
}


$systemFields = [

    'UNSUBSCRIBE_URL',
    'IMPRESSUM_URL',

    'KURS_1_BADGE_ICON',
    'KURS_1_BADGE_BG',
    'KURS_1_BADGE_BORDER',
    'KURS_1_BADGE_COLOR',

    'KURS_2_BADGE_ICON',
    'KURS_2_BADGE_BG',
    'KURS_2_BADGE_BORDER',
    'KURS_2_BADGE_COLOR',
];


$editableFields =
    array_values(
        array_filter(
            $templateFields,

            static fn(
                string $field
            ): bool =>
                !in_array(
                    $field,
                    $systemFields,
                    true
                )
        )
    );


$fieldOrder = [

    'BETREFF',
    'PREHEADER',
    'KATEGORIE',
    'UEBERSCHRIFT',

    'INHALT',
    'TEXT',

    'KURS_1_WOCHENTAG',
    'KURS_1_RHYTHMUS',
    'KURS_1_BADGE',
    'KURS_1_ZEIT_1',
    'KURS_1_ZEIT_2',
    'KURS_1_START',
    'KURS_1_ENDE',
    'KURS_1_PREIS',
    'KURS_1_PREIS_ALT',
    'KURS_1_URL',

    'KURS_2_WOCHENTAG',
    'KURS_2_RHYTHMUS',
    'KURS_2_BADGE',
    'KURS_2_ZEIT_1',
    'KURS_2_ZEIT_2',
    'KURS_2_START',
    'KURS_2_ENDE',
    'KURS_2_PREIS',
    'KURS_2_PREIS_ALT',
    'KURS_2_URL',

    'BUCHUNG_URL',

    'BUTTON_TEXT',
    'BUTTON_URL',
];


usort(
    $editableFields,

    static function (
        string $a,
        string $b
    ) use (
        $fieldOrder
    ): int {

        $pa =
            array_search(
                $a,
                $fieldOrder,
                true
            );

        $pb =
            array_search(
                $b,
                $fieldOrder,
                true
            );

        $pa =
            $pa === false
            ?
            999
            :
            $pa;

        $pb =
            $pb === false
            ?
            999
            :
            $pb;

        return
            $pa === $pb
            ?
            strcmp(
                $a,
                $b
            )
            :
            $pa
            <=>
            $pb;
    }
);


$isCourseTemplate =
    in_array(
        'KURS_1_WOCHENTAG',
        $editableFields,
        true
    );


$timeOptions =
    newsletterAdminTimeOptions();


$rhythmOptions = [
    'wöchentlich',
    '14-tägig',
];


$defaultValues = [

    'KURS_1_PREIS' =>
        '890 €',

    'KURS_1_PREIS_ALT' =>
        '980 €',

    'KURS_1_BADGE' =>
        'Früh/Spät',
];


$formValues = [];


foreach (
    $editableFields
    as
    $field
) {

    $formValues[$field] =
        array_key_exists(
            $field,
            $_POST
        )
        ?
        (string)$_POST[$field]
        :
        (string)(
            $defaultValues[$field]
            ?? ''
        );
}



/*
|--------------------------------------------------------------------------
| KURSDATEN – DATEPICKER
|--------------------------------------------------------------------------
*/

$courseDateUi = [];

foreach (
    [
        'KURS_1_START',
        'KURS_1_ENDE',
        'KURS_2_START',
        'KURS_2_ENDE',
    ]
    as $dateField
) {

    if (!array_key_exists($dateField, $formValues)) {
        continue;
    }

    $rawDate = trim((string)$formValues[$dateField]);

    $courseDateUi[$dateField] =
        newsletterAdminCourseDateToInput($rawDate);

    if ($rawDate !== '') {
        $formValues[$dateField] =
            newsletterAdminCourseDateToDisplay($rawDate);
    }
}


/*
|--------------------------------------------------------------------------
| RHYTHMUS
|--------------------------------------------------------------------------
*/

$rhythmUi = [];


foreach (
    [
        1,
        2,
    ]
    as
    $number
) {

    $field =
        'KURS_'
        . $number
        . '_RHYTHMUS';


    if (
        !in_array(
            $field,
            $editableFields,
            true
        )
    ) {

        continue;
    }


    if (
        $_SERVER['REQUEST_METHOD']
        ===
        'POST'
    ) {

        $choice =
            (string)(
                $_POST[
                    $field
                    . '_CHOICE'
                ]
                ?? ''
            );

        $custom =
            (string)(
                $_POST[
                    $field
                    . '_CUSTOM'
                ]
                ?? ''
            );


        $formValues[$field] =
            newsletterAdminResolveChoice(
                $choice,
                $custom
            );


        $rhythmUi[$field] = [

            'choice' =>
                $choice,

            'custom' =>
                $custom,
        ];


    } else {

        $rhythmUi[$field] =
            newsletterAdminChoiceUi(
                (string)(
                    $formValues[$field]
                    ?? ''
                ),
                $rhythmOptions
            );
    }
}


/*
|--------------------------------------------------------------------------
| UHRZEITEN
|--------------------------------------------------------------------------
|
| Start- und Endzeit werden unabhängig voneinander gewählt.
| Keine automatische Berechnung der Endzeit.
|
*/

$courseTimeParts = [];

foreach (
    [
        1,
        2,
    ]
    as $number
) {

    foreach (
        [
            'ZEIT_1',
            'ZEIT_2',
        ]
        as $suffix
    ) {

        $field =
            'KURS_'
            . $number
            . '_'
            . $suffix;

        if (
            !in_array(
                $field,
                $editableFields,
                true
            )
        ) {
            continue;
        }

        [
            $fallbackStart,
            $fallbackEnd,
        ] =
        newsletterAdminSplitTimeRange(
            (string)(
                $formValues[$field]
                ?? ''
            )
        );

        if (
            $_SERVER['REQUEST_METHOD']
            ===
            'POST'
        ) {

            $start =
                trim(
                    (string)(
                        $_POST[
                            $field
                            . '_START_CHOICE'
                        ]
                        ?? ''
                    )
                );

            $end =
                trim(
                    (string)(
                        $_POST[
                            $field
                            . '_END_CHOICE'
                        ]
                        ?? ''
                    )
                );

            $courseTimeParts[$field] = [
                'start' => $start,
                'end' => $end,
                'start_choice' => $start,
                'end_choice' => $end,
            ];

            $formValues[$field] =
                newsletterAdminComposeTimeRangeSoft(
                    $start,
                    $end
                );

        } else {

            $courseTimeParts[$field] = [
                'start' => $fallbackStart,
                'end' => $fallbackEnd,
                'start_choice' =>
                    in_array(
                        $fallbackStart,
                        $timeOptions,
                        true
                    )
                    ? $fallbackStart
                    : '',
                'end_choice' =>
                    in_array(
                        $fallbackEnd,
                        $timeOptions,
                        true
                    )
                    ? $fallbackEnd
                    : '',
            ];
        }
    }
}


/*
|--------------------------------------------------------------------------
| VERSANDZEIT
|--------------------------------------------------------------------------
*/

$scheduleMode =
    (string)(
        $_POST['schedule_mode']
        ?? 'next'
    );


if (
    !in_array(
        $scheduleMode,
        [
            'next',
            'scheduled',
        ],
        true
    )
) {

    $scheduleMode =
        'next';
}


$scheduleDate =
    trim(
        (string)(
            $_POST['schedule_date']
            ?? ''
        )
    );


$scheduleTime =
    trim(
        (string)(
            $_POST['schedule_time']
            ?? ''
        )
    );


/*
|--------------------------------------------------------------------------
| POST-AKTIONEN
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    ===
    'POST'
    &&
    $action !== 'login'
    &&
    $action !== 'logout'
) {

    try {

        newsletterAdminVerifyCsrf(
            (string)(
                $_POST['csrf']
                ?? ''
            )
        );


        /*
        | Kampagne abbrechen
        */

        if (
            $action === 'cancel_campaign'
        ) {

            $campaignId =
                (int)(
                    $_POST['campaign_id']
                    ?? 0
                );


            newsletterCancelCampaign(
                $pdo,
                $campaignId
            );


            $returnTemplate =
                trim(
                    (string)(
                        $_POST['return_template']
                        ?? ''
                    )
                );


            $query =
                '?cancelled='
                . $campaignId;


            if (
                $returnTemplate !== ''
            ) {

                $query .=
                    '&template='
                    . rawurlencode(
                        $returnTemplate
                    );
            }


            header(
                'Location: /intern/newsletter.php'
                . $query
            );

            exit;
        }


        /*
        | Vorlage prüfen
        */

        if (
            $selectedTemplate === ''
        ) {

            throw new RuntimeException(
                'Bitte wähle eine Vorlage aus.'
            );
        }


        if (
            in_array(
                $action,
                [
                    'preview',
                    'test_send',
                    'prepare_send',
                    'queue_send',
                ],
                true
            )
        ) {

            /*
            | NUR die aktuell ausgewählte Vorlage prüfen.
            */

            $fieldErrors =
                newsletterAdminValidateCurrentTemplate(
                    $editableFields,
                    $formValues,
                    $courseTimeParts
                );


            /*
            | Versandzeitpunkt nur für echten Versand prüfen.
            */

            if (
                in_array(
                    $action,
                    [
                        'prepare_send',
                        'queue_send',
                    ],
                    true
                )
                &&
                $scheduleMode
                ===
                'scheduled'
            ) {

                if (
                    $scheduleDate === ''
                ) {

                    $fieldErrors['schedule_date'] =
                        'Bitte wähle ein Versanddatum.';
                }


                if (
                    $scheduleTime === ''
                ) {

                    $fieldErrors['schedule_time'] =
                        'Bitte wähle eine Versandzeit.';
                }


                if (
                    $scheduleDate !== ''
                    &&
                    $scheduleTime !== ''
                ) {

                    try {

                        newsletterAdminResolveScheduleUtc(
                            $scheduleMode,
                            $scheduleDate,
                            $scheduleTime
                        );


                    } catch (
                        Throwable $scheduleException
                    ) {

                        $fieldErrors['schedule_date'] =
                            $scheduleException->getMessage();

                        $fieldErrors['schedule_time'] =
                            $scheduleException->getMessage();
                    }
                }
            }


            if (
                $fieldErrors !== []
            ) {

                throw new RuntimeException(
                    'Bitte korrigiere die rot markierten Felder.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | VORSCHAU-SUBSCRIBER
        |--------------------------------------------------------------------------
        */

        $previewSubscriber = [

            'id' =>
                0,

            'firstname' =>
                'Vorschau',

            'email' =>
                'preview@gemeinsamdenmeistermeistern.de',

            'confirmed_at' =>
                '2026-01-01 00:00:00',
        ];


        /*
        |--------------------------------------------------------------------------
        | RENDERING
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $action,
                [
                    'preview',
                    'test_send',
                    'prepare_send',
                    'queue_send',
                ],
                true
            )
        ) {

            $previewHtml =
                renderNewsletter(
                    $selectedTemplate,
                    $formValues,
                    $previewSubscriber,
                    $config
                );
        }


        /*
        |--------------------------------------------------------------------------
        | TESTMAIL
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'test_send'
        ) {

            $testEmail =
                trim(
                    (string)(
                        $config[
                            'newsletter_test_email'
                        ]
                        ?? ''
                    )
                );


            if (
                !filter_var(
                    $testEmail,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                throw new RuntimeException(
                    'newsletter_test_email fehlt oder ist ungültig in community-config.php.'
                );
            }


            $unsubscribeUrl =
                newsletterUnsubscribeUrl(
                    $previewSubscriber,
                    $config
                );


            $fromEmail =
                (string)(
                    $config[
                        'newsletter_from_email'
                    ]
                    ??
                    $config['mail_from']
                );


            $domain =
                (string)substr(
                    strrchr(
                        $fromEmail,
                        '@'
                    )
                    ?:
                    '@gemeinsamdenmeistermeistern.de',
                    1
                );


            newsletterSendMail(
                $testEmail,
                'Newsletter-Test',
                '[TEST] '
                .
                trim(
                    (string)(
                        $formValues['BETREFF']
                        ?? 'Newsletter'
                    )
                ),
                $previewHtml,
                $unsubscribeUrl,
                '<gdmm-newsletter-test-'
                .
                bin2hex(
                    random_bytes(
                        8
                    )
                )
                .
                '@'
                .
                $domain
                .
                '>',
                $config
            );


            $success =
                'Testmail wurde an '
                . $testEmail
                . ' gesendet.';
        }


        /*
        |--------------------------------------------------------------------------
        | VERSAND VORBEREITEN
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'prepare_send'
        ) {

            if (
                $subscriberCount < 1
            ) {

                throw new RuntimeException(
                    'Es gibt aktuell keine Newsletter-Abonnenten.'
                );
            }


            $prepareSend =
                true;
        }


        /*
        |--------------------------------------------------------------------------
        | VERSAND EINPLANEN
        |--------------------------------------------------------------------------
        */

        if (
            $action === 'queue_send'
        ) {

            if (
                (string)(
                    $_POST['send_confirmation']
                    ?? ''
                )
                !==
                'VERSENDEN'
            ) {

                throw new RuntimeException(
                    'Bitte bestätige den Versand mit dem Wort VERSENDEN.'
                );
            }


            $scheduledAtUtc =
                newsletterAdminResolveScheduleUtc(
                    $scheduleMode,
                    $scheduleDate,
                    $scheduleTime
                );


            $campaignId =
                newsletterQueueCampaign(
                    $pdo,
                    $selectedTemplate,
                    $formValues,
                    $scheduledAtUtc
                );


            header(
                'Location: /intern/newsletter.php?queued='
                .
                $campaignId
                .
                '&template='
                .
                rawurlencode(
                    $selectedTemplate
                )
            );

            exit;
        }


    } catch (
        Throwable $exception
    ) {

        $error =
            $exception->getMessage();
    }
}


/*
|--------------------------------------------------------------------------
| ERFOLGSMELDUNGEN
|--------------------------------------------------------------------------
*/

if (
    isset(
        $_GET['queued']
    )
    &&
    ctype_digit(
        (string)$_GET['queued']
    )
) {

    $success =
        'Newsletter #'
        .
        (int)$_GET['queued']
        .
        ' wurde für den Versand eingeplant.';
}


if (
    isset(
        $_GET['cancelled']
    )
    &&
    ctype_digit(
        (string)$_GET['cancelled']
    )
) {

    $success =
        'Newsletter #'
        .
        (int)$_GET['cancelled']
        .
        ' wurde gestoppt.';
}


/*
|--------------------------------------------------------------------------
| RICH-TEXT VORSCHAU
|--------------------------------------------------------------------------
*/

$richPreviewValue = '';


if (
    isset(
        $formValues['INHALT']
    )
) {

    try {

        $richPreviewValue =
            newsletterSanitizeRichHtml(
                $formValues['INHALT']
            );


    } catch (
        Throwable
    ) {

        $richPreviewValue =
            '';
    }
}


/*
|--------------------------------------------------------------------------
| KAMPAGNEN
|--------------------------------------------------------------------------
*/

$recentCampaigns = [];


try {

    $recentCampaigns =
        newsletterRecentCampaigns(
            $pdo,
            10
        );


} catch (
    Throwable $exception
) {

    if (
        $error === null
    ) {

        $error =
            'Versandtabellen fehlen noch: '
            .
            $exception->getMessage();
    }
}


$course2Used =
    newsletterAdminHasCourse2Data(
        $formValues,
        $courseTimeParts
    );


$course1HasErrors = false;
$course2HasErrors = false;


foreach (
    array_keys(
        $fieldErrors
    )
    as
    $field
) {

    if (
        str_starts_with(
            $field,
            'KURS_1_'
        )
    ) {

        $course1HasErrors =
            true;
    }


    if (
        str_starts_with(
            $field,
            'KURS_2_'
        )
    ) {

        $course2HasErrors =
            true;
    }
}

?>
<!DOCTYPE html>

<html lang="de">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        GDMM Newsletter
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f5f8;
            color: #161c29;
            font-family: Arial, Helvetica, sans-serif;
        }

        .page {
            width: min(
                1220px,
                calc(100% - 32px)
            );
            margin: 28px auto 60px;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .brand {
            margin-bottom: 6px;
            color: #0043FA;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 32px;
        }

        .intro {
            margin: 8px 0 0;
            color: #667085;
            font-size: 15px;
        }

        .top-actions {
            display: flex;
            gap: 9px;
            align-items: center;
        }

        .logout,
        .clear-button {

            border: 1px solid #d9e0ea;
            border-radius: 9px;
            background: #fff;
            padding: 10px 13px;
            font-weight: 700;
            cursor: pointer;
            color: #344054;
        }

        .clear-button {
            border-color: #efb4b4;
            color: #9b2020;
        }

        .layout {
            display: grid;
            grid-template-columns:
                minmax(360px, 455px)
                minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e9f0;
            border-radius: 18px;
            box-shadow:
                0 7px 24px
                rgba(20,32,55,.06);
        }

        .form-card {
            padding: 24px;
        }

        .preview-card {
            overflow: hidden;
            position: sticky;
            top: 18px;
        }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 17px 20px;
            border-bottom: 1px solid #e5e9f0;
        }

        .preview-title {
            font-size: 14px;
            font-weight: 800;
        }

        .preview-note {
            color: #667085;
            font-size: 12px;
        }

        .field {
            margin-bottom: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: #28313d;
            font-size: 13px;
            font-weight: 700;
        }

        .field input,
        .field textarea,
        .field select,
        .schedule-box input {

            width: 100%;

            border: 1px solid #ccd4df;
            border-radius: 10px;

            background: #fff;
            color: #161c29;

            font: inherit;
            font-size: 15px;

            outline: none;

            transition:
                border-color .15s,
                box-shadow .15s;
        }

        .field input,
        .field select,
        .schedule-box input {

            height: 44px;
            padding: 0 12px;
        }

        .field textarea {

            min-height: 100px;
            padding: 11px 12px;
            line-height: 1.5;
            resize: vertical;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus,
        .rich-editor:focus,
        .schedule-box input:focus {

            border-color: #0043FA;

            box-shadow:
                0 0 0 3px
                rgba(0,67,250,.10);
        }

        .field.has-error > input,
        .field.has-error > textarea,
        .field.has-error > select,
        .field.has-error > .rich-wrap,
        .field.has-error > .time-grid,
        .schedule-fields .has-error > input {

            border-color: #d92d20 !important;

            box-shadow:
                0 0 0 3px
                rgba(217,45,32,.08);
        }

        .field-error {

            margin-top: 6px;

            color: #b42318;

            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .template-field {
            padding-bottom: 18px;
            border-bottom: 1px solid #edf0f4;
        }

        .rich-wrap {
            overflow: hidden;
            border: 1px solid #ccd4df;
            border-radius: 12px;
            background: #fff;
        }

        .rich-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 8px;
            border-bottom: 1px solid #e5e9f0;
            background: #f8faff;
        }

        .rich-tool {
            min-width: 34px;
            height: 34px;
            padding: 0 9px;
            border: 1px solid #d8dfeb;
            border-radius: 7px;
            background: #fff;
            color: #1f2937;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
        }

        .rich-editor {
            min-height: 180px;
            padding: 15px 16px;
            outline: none;
            color: #1f2937;
            font-size: 15px;
            line-height: 1.55;
        }

        .rich-editor:empty::before {
            content: attr(data-placeholder);
            color: #98a2b3;
            pointer-events: none;
        }

        .rich-editor .gdmm-formula {
            font-family: Georgia, "Times New Roman", serif;
            font-style: italic;
            white-space: nowrap;
        }

        .rich-help {
            margin: 7px 2px 0;
            color: #667085;
            font-size: 12px;
            line-height: 1.45;
        }

        .course-box {

            margin: 18px 0;

            border: 1px solid #dce4ef;
            border-radius: 13px;

            background: #fbfcfe;

            overflow: hidden;
        }

        .course-box summary {

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;

            padding: 15px 16px;

            cursor: pointer;

            font-weight: 800;

            list-style: none;
        }

        .course-box summary::-webkit-details-marker {
            display: none;
        }

        .course-box summary::after {
            content: '+';
            font-size: 22px;
            color: #667085;
        }

        .course-box[open] summary::after {
            content: '−';
        }

        .course-box .course-content {
            padding: 2px 16px 16px;
            border-top: 1px solid #edf0f4;
        }


        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .time-grid {

            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 10px;

            border: 1px solid transparent;

            border-radius: 11px;
        }

        .time-part label {
            font-size: 12px;
        }

        .custom-input {
            margin-top: 7px;
        }

        .schedule-box {

            margin-top: 22px;

            padding: 16px;

            border: 1px solid #dce4ef;
            border-radius: 12px;

            background: #f8faff;
        }

        .schedule-box h3 {
            margin: 0 0 12px;
            font-size: 15px;
        }

        .radio-row {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .radio-row label {
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 0;
            font-weight: 600;
            font-size: 13px;
        }

        .radio-row input {
            width: auto;
            height: auto;
        }

        .schedule-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .schedule-fields .has-error input {
            border-color: #d92d20;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 16px;
        }

        .button {

            min-height: 45px;

            border: 0;
            border-radius: 9px;

            padding: 0 16px;

            background: #ACF20D;

            color: #17211a;

            font-size: 14px;
            font-weight: 800;

            cursor: pointer;
        }

        .button-secondary {
            background: #eef3ff;
            color: #0043FA;
        }

        .button-dark {
            background: #17211a;
            color: #fff;
        }

        .confirm {

            margin-top: 18px;

            padding: 16px;

            border: 1px solid #b8c8ff;
            border-radius: 12px;

            background: #f7f9ff;
        }

        .confirm p {
            font-size: 13px;
            line-height: 1.5;
            color: #475467;
        }

        .message {

            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 11px;

            font-size: 14px;
            line-height: 1.45;
        }

        .message.error {
            border: 1px solid #f0b7b7;
            background: #fff3f3;
            color: #8a1f1f;
        }

        .message.success {
            border: 1px solid #b8dfc4;
            background: #f1fbf4;
            color: #176336;
        }

        .empty-preview {
            display: grid;
            min-height: 720px;
            place-items: center;
            padding: 30px;
            color: #667085;
            text-align: center;
        }

        iframe {
            display: block;
            width: 100%;
            height: 900px;
            border: 0;
            background: #f3f5f8;
        }

        .stats {
            margin-top: 24px;
            padding: 22px;
        }

        .stats-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 14px;
        }

        .stats h2 {
            margin: 0;
            font-size: 22px;
        }

        .count-badge {
            padding: 9px 13px;
            border-radius: 999px;
            background: #eef3ff;
            color: #0043FA;
            font-weight: 800;
        }

        .table-wrap {
            overflow: auto;
        }

        .history {
            width: 100%;
            border-collapse: collapse;
        }

        .history th,
        .history td {
            padding: 12px 10px;
            border-bottom: 1px solid #edf0f4;
            text-align: left;
            white-space: nowrap;
        }

        .history th {
            color: #667085;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .status {
            font-weight: 800;
        }

        .status.completed {
            color: #176336;
        }

        .status.sending,
        .status.queued {
            color: #0043FA;
        }

        .status.cancelled {
            color: #8a1f1f;
        }

        .muted {
            color: #667085;
        }

        .cancel-button {
            border: 1px solid #efb4b4;
            background: #fff5f5;
            color: #9b2020;
            border-radius: 8px;
            padding: 7px 10px;
            font-weight: 700;
            cursor: pointer;
        }

        .status-note {
            font-size: 12px;
            color: #667085;
        }


        @media (
            max-width: 900px
        ) {

            .layout {
                grid-template-columns: 1fr;
            }

            .preview-card {
                position: static;
            }

            iframe {
                height: 780px;
            }

            .topbar {
                align-items: center;
            }

            .top-actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            h1 {
                font-size: 28px;
            }
        }

    </style>

</head>

<body>

<div class="page">

    <div class="topbar">

        <header>

            <div class="brand">
                GDMM INTERN
            </div>

            <h1>
                Newsletter erstellen
            </h1>

            <p class="intro">
                Vorlage auswählen, vollständig ausfüllen,
                prüfen und versenden.
            </p>

        </header>


        <div class="top-actions">

            <button
                type="button"
                class="clear-button"
                id="clear-form"
            >
                Formular leeren
            </button>


            <form method="post">

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= adminEscape(
                        $csrf
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="logout"
                >

                <button
                    class="logout"
                    type="submit"
                >
                    Abmelden
                </button>

            </form>

        </div>

    </div>


    <?php if (
        $error !== null
    ): ?>

        <div class="message error">
            <?= adminEscape(
                $error
            ) ?>
        </div>

    <?php endif; ?>


    <?php if (
        $success !== null
    ): ?>

        <div class="message success">
            <?= adminEscape(
                $success
            ) ?>
        </div>

    <?php endif; ?>


    <div class="layout">

        <section class="card form-card">

            <form
                method="post"
                id="newsletter-form"
                autocomplete="off"
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= adminEscape(
                        $csrf
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="template"
                    value="<?= adminEscape(
                        $selectedTemplate
                    ) ?>"
                >


                <div class="field template-field">

                    <label for="template-selector">
                        Vorlage
                    </label>

                    <select
                        id="template-selector"
                        autocomplete="off"
                    >

                        <?php foreach (
                            $templates
                            as
                            $template
                        ): ?>

                            <option
                                value="<?= adminEscape(
                                    (string)$template['key']
                                ) ?>"
                                <?= (
                                    $selectedTemplate
                                    ===
                                    $template['key']
                                )
                                    ?
                                    'selected'
                                    :
                                    ''
                                ?>
                            >

                                <?= adminEscape(
                                    (string)$template['label']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <?php foreach (
                    [
                        'BETREFF',
                        'PREHEADER',
                        'KATEGORIE',
                        'UEBERSCHRIFT',
                    ]
                    as
                    $field
                ): ?>

                    <?php if (
                        !in_array(
                            $field,
                            $editableFields,
                            true
                        )
                    ) {
                        continue;
                    } ?>


                    <div
                        class="<?= newsletterAdminFieldClass(
                            $field,
                            $fieldErrors
                        ) ?>"
                    >

                        <label
                            for="<?= adminEscape(
                                $field
                            ) ?>"
                        >
                            <?= adminEscape(
                                newsletterAdminFieldLabel(
                                    $field
                                )
                            ) ?>
                        </label>

                        <input
                            type="text"
                            id="<?= adminEscape(
                                $field
                            ) ?>"
                            name="<?= adminEscape(
                                $field
                            ) ?>"
                            value="<?= adminEscape(
                                (string)(
                                    $formValues[$field]
                                    ?? ''
                                )
                            ) ?>"
                            placeholder="<?= adminEscape(
                                newsletterAdminFieldPlaceholder(
                                    $field
                                )
                            ) ?>"
                            autocomplete="off"
                        >

                        <?php if (
                            isset(
                                $fieldErrors[$field]
                            )
                        ): ?>

                            <div class="field-error">
                                <?= adminEscape(
                                    $fieldErrors[$field]
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>


                <?php if (
                    in_array(
                        'INHALT',
                        $editableFields,
                        true
                    )
                ): ?>

                    <div
                        class="<?= newsletterAdminFieldClass(
                            'INHALT',
                            $fieldErrors
                        ) ?>"
                    >

                        <label>
                            Inhalt
                        </label>

                        <div class="rich-wrap">

                            <div
                                class="rich-toolbar"
                                aria-label="Formatierung"
                            >

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="bold"
                                >
                                    <strong>B</strong>
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="italic"
                                >
                                    <em>I</em>
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="insertUnorderedList"
                                >
                                    • Liste
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="insertOrderedList"
                                >
                                    1. Liste
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="subscript"
                                >
                                    x₂
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    data-rich-command="superscript"
                                >
                                    x²
                                </button>

                                <button
                                    class="rich-tool"
                                    type="button"
                                    id="formula-button"
                                >
                                    ƒ Formel
                                </button>

                            </div>


                            <div
                                id="rich-editor"
                                class="rich-editor"
                                contenteditable="true"
                                data-placeholder="Schreib hier den Inhalt der Kachel …"
                                spellcheck="true"
                            ><?= $richPreviewValue ?></div>

                        </div>


                        <input
                            type="hidden"
                            id="INHALT"
                            name="INHALT"
                            value="<?= adminEscape(
                                (string)(
                                    $formValues['INHALT']
                                    ?? ''
                                )
                            ) ?>"
                        >


                        <?php if (
                            isset(
                                $fieldErrors['INHALT']
                            )
                        ): ?>

                            <div class="field-error">
                                <?= adminEscape(
                                    $fieldErrors['INHALT']
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    in_array(
                        'TEXT',
                        $editableFields,
                        true
                    )
                ): ?>

                    <div
                        class="<?= newsletterAdminFieldClass(
                            'TEXT',
                            $fieldErrors
                        ) ?>"
                    >

                        <label for="TEXT">
                            Kurzer Text
                        </label>

                        <textarea
                            id="TEXT"
                            name="TEXT"
                            autocomplete="off"
                            placeholder="<?= adminEscape(
                                newsletterAdminFieldPlaceholder(
                                    'TEXT'
                                )
                            ) ?>"
                        ><?= adminEscape(
                            (string)(
                                $formValues['TEXT']
                                ?? ''
                            )
                        ) ?></textarea>


                        <?php if (
                            isset(
                                $fieldErrors['TEXT']
                            )
                        ): ?>

                            <div class="field-error">
                                <?= adminEscape(
                                    $fieldErrors['TEXT']
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <?php if (
                    $isCourseTemplate
                ): ?>

                    <?php foreach (
                        [
                            1,
                            2,
                        ]
                        as
                        $courseNumber
                    ): ?>

                        <?php

                        $prefix =
                            'KURS_'
                            . $courseNumber
                            . '_';

                        $hasErrors =
                            $courseNumber === 1
                            ?
                            $course1HasErrors
                            :
                            $course2HasErrors;

                        $open =
                            $hasErrors
                            ||
                            (
                                $courseNumber === 1
                                &&
                                $_SERVER['REQUEST_METHOD']
                                !==
                                'POST'
                            )
                            ||
                            (
                                $courseNumber === 2
                                &&
                                $course2Used
                            );

                        ?>


                        <details
                            class="course-box <?= $hasErrors ? 'has-error' : '' ?>"
                            data-course="<?= $courseNumber ?>"
                            <?= $open ? 'open' : '' ?>
                        >

                            <summary>

                                <span>
                                    Kurs <?= $courseNumber ?><?= $courseNumber === 2 ? ' · optional' : '' ?>
                                </span>


                            </summary>


                            <div class="course-content">

                                <div
                                    class="<?= newsletterAdminFieldClass(
                                        $prefix
                                        . 'WOCHENTAG',
                                        $fieldErrors
                                    ) ?>"
                                >

                                    <label
                                        for="<?= $prefix ?>WOCHENTAG"
                                    >
                                        Wochentag
                                    </label>

                                    <select
                                        id="<?= $prefix ?>WOCHENTAG"
                                        name="<?= $prefix ?>WOCHENTAG"
                                    >

                                        <option value="">
                                            – auswählen –
                                        </option>

                                        <?php foreach (
                                            [
                                                'Montag',
                                                'Dienstag',
                                                'Mittwoch',
                                                'Donnerstag',
                                                'Freitag',
                                                'Samstag',
                                                'Sonntag',
                                            ]
                                            as
                                            $weekday
                                        ): ?>

                                            <option
                                                value="<?= adminEscape(
                                                    $weekday
                                                ) ?>"
                                                <?= (
                                                    (
                                                        $formValues[
                                                            $prefix
                                                            . 'WOCHENTAG'
                                                        ]
                                                        ?? ''
                                                    )
                                                    ===
                                                    $weekday
                                                )
                                                    ?
                                                    'selected'
                                                    :
                                                    ''
                                                ?>
                                            >
                                                <?= adminEscape(
                                                    $weekday
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>


                                    <?php if (
                                        isset(
                                            $fieldErrors[
                                                $prefix
                                                . 'WOCHENTAG'
                                            ]
                                        )
                                    ): ?>

                                        <div class="field-error">
                                            <?= adminEscape(
                                                $fieldErrors[
                                                    $prefix
                                                    . 'WOCHENTAG'
                                                ]
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="<?= newsletterAdminFieldClass(
                                        $prefix
                                        . 'RHYTHMUS',
                                        $fieldErrors
                                    ) ?>"
                                    data-rhythm-wrap
                                >

                                    <label>
                                        Rhythmus
                                    </label>


                                    <select
                                        name="<?= $prefix ?>RHYTHMUS_CHOICE"
                                        data-rhythm-choice
                                    >

                                        <option value="">
                                            – auswählen –
                                        </option>


                                        <?php foreach (
                                            $rhythmOptions
                                            as
                                            $option
                                        ): ?>

                                            <option
                                                value="<?= adminEscape(
                                                    $option
                                                ) ?>"
                                                <?= (
                                                    (
                                                        $rhythmUi[
                                                            $prefix
                                                            . 'RHYTHMUS'
                                                        ]['choice']
                                                        ?? ''
                                                    )
                                                    ===
                                                    $option
                                                )
                                                    ?
                                                    'selected'
                                                    :
                                                    ''
                                                ?>
                                            >
                                                <?= adminEscape(
                                                    $option
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>


                                        <option
                                            value="__custom__"
                                            <?= (
                                                (
                                                    $rhythmUi[
                                                        $prefix
                                                        . 'RHYTHMUS'
                                                    ]['choice']
                                                    ?? ''
                                                )
                                                ===
                                                '__custom__'
                                            )
                                                ?
                                                'selected'
                                                :
                                                ''
                                            ?>
                                        >
                                            Individuell …
                                        </option>

                                    </select>


                                    <input
                                        type="text"
                                        class="custom-input"
                                        name="<?= $prefix ?>RHYTHMUS_CUSTOM"
                                        data-rhythm-custom
                                        value="<?= adminEscape(
                                            (string)(
                                                $rhythmUi[
                                                    $prefix
                                                    . 'RHYTHMUS'
                                                ]['custom']
                                                ?? ''
                                            )
                                        ) ?>"
                                        placeholder="Rhythmus eingeben"
                                        autocomplete="off"
                                        <?= (
                                            (
                                                $rhythmUi[
                                                    $prefix
                                                    . 'RHYTHMUS'
                                                ]['choice']
                                                ?? ''
                                            )
                                            ===
                                            '__custom__'
                                        )
                                            ?
                                            ''
                                            :
                                            'hidden'
                                        ?>
                                    >


                                    <input
                                        type="hidden"
                                        name="<?= $prefix ?>RHYTHMUS"
                                        value="<?= adminEscape(
                                            (string)(
                                                $formValues[
                                                    $prefix
                                                    . 'RHYTHMUS'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                        data-rhythm-hidden
                                    >


                                    <?php if (
                                        isset(
                                            $fieldErrors[
                                                $prefix
                                                . 'RHYTHMUS'
                                            ]
                                        )
                                    ): ?>

                                        <div class="field-error">
                                            <?= adminEscape(
                                                $fieldErrors[
                                                    $prefix
                                                    . 'RHYTHMUS'
                                                ]
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="<?= newsletterAdminFieldClass(
                                        $prefix
                                        . 'BADGE',
                                        $fieldErrors
                                    ) ?>"
                                >

                                    <label
                                        for="<?= $prefix ?>BADGE"
                                    >
                                        Zeitmodell
                                    </label>

                                    <select
                                        id="<?= $prefix ?>BADGE"
                                        name="<?= $prefix ?>BADGE"
                                    >

                                        <option value="">
                                            – auswählen –
                                        </option>


                                        <?php foreach (
                                            [
                                                'Früh/Spät',
                                                'Spät',
                                                'Früh',
                                            ]
                                            as
                                            $badge
                                        ): ?>

                                            <option
                                                value="<?= adminEscape(
                                                    $badge
                                                ) ?>"
                                                <?= (
                                                    (
                                                        $formValues[
                                                            $prefix
                                                            . 'BADGE'
                                                        ]
                                                        ?? ''
                                                    )
                                                    ===
                                                    $badge
                                                )
                                                    ?
                                                    'selected'
                                                    :
                                                    ''
                                                ?>
                                            >
                                                <?= adminEscape(
                                                    $badge
                                                ) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>


                                    <?php if (
                                        isset(
                                            $fieldErrors[
                                                $prefix
                                                . 'BADGE'
                                            ]
                                        )
                                    ): ?>

                                        <div class="field-error">
                                            <?= adminEscape(
                                                $fieldErrors[
                                                    $prefix
                                                    . 'BADGE'
                                                ]
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>


                                <?php foreach (
                                    [
                                        'ZEIT_1',
                                        'ZEIT_2',
                                    ]
                                    as
                                    $timeSuffix
                                ): ?>

                                    <?php
                                    $timeField =
                                        $prefix
                                        . $timeSuffix;
                                    ?>

                                    <div
                                        class="<?= newsletterAdminFieldClass(
                                            $timeField,
                                            $fieldErrors
                                        ) ?>"
                                        data-time-range="<?= adminEscape(
                                            $timeField
                                        ) ?>"
                                    >

                                        <label>
                                            <?= adminEscape(
                                                newsletterAdminFieldLabel(
                                                    $timeField
                                                )
                                            ) ?>
                                        </label>

                                        <div class="time-grid">

                                            <div class="time-part">

                                                <label>
                                                    Startzeit
                                                </label>

                                                <select
                                                    name="<?= $timeField ?>_START_CHOICE"
                                                    data-time-start-choice
                                                >

                                                    <option value="">
                                                        --:--
                                                    </option>

                                                    <?php foreach (
                                                        $timeOptions
                                                        as
                                                        $timeOption
                                                    ): ?>

                                                        <option
                                                            value="<?= $timeOption ?>"
                                                            <?= (
                                                                (
                                                                    $courseTimeParts[
                                                                        $timeField
                                                                    ]['start_choice']
                                                                    ?? ''
                                                                )
                                                                ===
                                                                $timeOption
                                                            )
                                                                ? 'selected'
                                                                : ''
                                                            ?>
                                                        >
                                                            <?= $timeOption ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                            <div class="time-part">

                                                <label>
                                                    Endzeit
                                                </label>

                                                <select
                                                    name="<?= $timeField ?>_END_CHOICE"
                                                    data-time-end-choice
                                                >

                                                    <option value="">
                                                        --:--
                                                    </option>

                                                    <?php foreach (
                                                        $timeOptions
                                                        as
                                                        $timeOption
                                                    ): ?>

                                                        <option
                                                            value="<?= $timeOption ?>"
                                                            <?= (
                                                                (
                                                                    $courseTimeParts[
                                                                        $timeField
                                                                    ]['end_choice']
                                                                    ?? ''
                                                                )
                                                                ===
                                                                $timeOption
                                                            )
                                                                ? 'selected'
                                                                : ''
                                                            ?>
                                                        >
                                                            <?= $timeOption ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                        </div>

                                        <input
                                            type="hidden"
                                            id="<?= $timeField ?>"
                                            name="<?= $timeField ?>"
                                            value="<?= adminEscape(
                                                (string)(
                                                    $formValues[
                                                        $timeField
                                                    ]
                                                    ?? ''
                                                )
                                            ) ?>"
                                            data-time-hidden
                                        >

                                        <?php if (
                                            isset(
                                                $fieldErrors[
                                                    $timeField
                                                ]
                                            )
                                        ): ?>

                                            <div class="field-error">
                                                <?= adminEscape(
                                                    $fieldErrors[
                                                        $timeField
                                                    ]
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                <?php endforeach; ?>


                                <div class="field-row">

                                    <?php foreach (
                                        [
                                            'START',
                                            'ENDE',
                                        ]
                                        as
                                        $dateSuffix
                                    ): ?>

                                        <?php

                                        $dateField =
                                            $prefix
                                            . $dateSuffix;

                                        ?>


                                        <div
                                            class="<?= newsletterAdminFieldClass(
                                                $dateField,
                                                $fieldErrors
                                            ) ?>"
                                        >

                                            <label
                                                for="<?= $dateField ?>"
                                            >
                                                <?= adminEscape(
                                                    newsletterAdminFieldLabel(
                                                        $dateField
                                                    )
                                                ) ?>
                                            </label>

                                            <input
                                                type="date"
                                                id="<?= $dateField ?>"
                                                name="<?= $dateField ?>"
                                                value="<?= adminEscape(
                                                    (string)(
                                                        $courseDateUi[
                                                            $dateField
                                                        ]
                                                        ?? ''
                                                    )
                                                ) ?>"
                                                data-course-date
                                                autocomplete="off"
                                            >


                                            <?php if (
                                                isset(
                                                    $fieldErrors[
                                                        $dateField
                                                    ]
                                                )
                                            ): ?>

                                                <div class="field-error">
                                                    <?= adminEscape(
                                                        $fieldErrors[
                                                            $dateField
                                                        ]
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                </div>


                                <div class="field-row">

                                    <?php foreach (
                                        [
                                            'PREIS',
                                            'PREIS_ALT',
                                        ]
                                        as
                                        $priceSuffix
                                    ): ?>

                                        <?php

                                        $priceField =
                                            $prefix
                                            . $priceSuffix;

                                        ?>


                                        <div
                                            class="<?= newsletterAdminFieldClass(
                                                $priceField,
                                                $fieldErrors
                                            ) ?>"
                                        >

                                            <label
                                                for="<?= $priceField ?>"
                                            >
                                                <?= adminEscape(
                                                    newsletterAdminFieldLabel(
                                                        $priceField
                                                    )
                                                ) ?>
                                            </label>

                                            <input
                                                type="text"
                                                id="<?= $priceField ?>"
                                                name="<?= $priceField ?>"
                                                value="<?= adminEscape(
                                                    (string)(
                                                        $formValues[
                                                            $priceField
                                                        ]
                                                        ?? ''
                                                    )
                                                ) ?>"
                                                placeholder="<?= adminEscape(
                                                    newsletterAdminFieldPlaceholder(
                                                        $priceField
                                                    )
                                                ) ?>"
                                                autocomplete="off"
                                            >


                                            <?php if (
                                                isset(
                                                    $fieldErrors[
                                                        $priceField
                                                    ]
                                                )
                                            ): ?>

                                                <div class="field-error">
                                                    <?= adminEscape(
                                                        $fieldErrors[
                                                            $priceField
                                                        ]
                                                    ) ?>
                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    <?php endforeach; ?>

                                </div>


                                <div
                                    class="<?= newsletterAdminFieldClass(
                                        $prefix
                                        . 'URL',
                                        $fieldErrors
                                    ) ?>"
                                >

                                    <label
                                        for="<?= $prefix ?>URL"
                                    >
                                        Link zur Kurskachel
                                    </label>

                                    <input
                                        type="text"
                                        id="<?= $prefix ?>URL"
                                        name="<?= $prefix ?>URL"
                                        value="<?= adminEscape(
                                            (string)(
                                                $formValues[
                                                    $prefix
                                                    . 'URL'
                                                ]
                                                ?? ''
                                            )
                                        ) ?>"
                                        placeholder="<?= adminEscape(
                                            newsletterAdminFieldPlaceholder(
                                                $prefix
                                                . 'URL'
                                            )
                                        ) ?>"
                                        inputmode="url"
                                        autocomplete="off"
                                    >


                                    <?php if (
                                        isset(
                                            $fieldErrors[
                                                $prefix
                                                . 'URL'
                                            ]
                                        )
                                    ): ?>

                                        <div class="field-error">
                                            <?= adminEscape(
                                                $fieldErrors[
                                                    $prefix
                                                    . 'URL'
                                                ]
                                            ) ?>
                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </details>

                    <?php endforeach; ?>


                    <?php if (
                        in_array(
                            'BUCHUNG_URL',
                            $editableFields,
                            true
                        )
                    ): ?>

                        <div
                            class="<?= newsletterAdminFieldClass(
                                'BUCHUNG_URL',
                                $fieldErrors
                            ) ?>"
                        >

                            <label for="BUCHUNG_URL">
                                Allgemeine Buchungsseite
                            </label>

                            <input
                                type="text"
                                id="BUCHUNG_URL"
                                name="BUCHUNG_URL"
                                value="<?= adminEscape(
                                    (string)(
                                        $formValues[
                                            'BUCHUNG_URL'
                                        ]
                                        ?? ''
                                    )
                                ) ?>"
                                placeholder="<?= adminEscape(
                                    newsletterAdminFieldPlaceholder(
                                        'BUCHUNG_URL'
                                    )
                                ) ?>"
                                inputmode="url"
                                autocomplete="off"
                            >


                            <?php if (
                                isset(
                                    $fieldErrors[
                                        'BUCHUNG_URL'
                                    ]
                                )
                            ): ?>

                                <div class="field-error">
                                    <?= adminEscape(
                                        $fieldErrors[
                                            'BUCHUNG_URL'
                                        ]
                                    ) ?>
                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>


                <?php foreach (
                    [
                        'BUTTON_TEXT',
                        'BUTTON_URL',
                    ]
                    as
                    $field
                ): ?>

                    <?php if (
                        !in_array(
                            $field,
                            $editableFields,
                            true
                        )
                    ) {
                        continue;
                    } ?>


                    <div
                        class="<?= newsletterAdminFieldClass(
                            $field,
                            $fieldErrors
                        ) ?>"
                    >

                        <label
                            for="<?= $field ?>"
                        >
                            <?= adminEscape(
                                newsletterAdminFieldLabel(
                                    $field
                                )
                            ) ?>
                        </label>

                        <input
                            type="text"
                            id="<?= $field ?>"
                            name="<?= $field ?>"
                            value="<?= adminEscape(
                                (string)(
                                    $formValues[$field]
                                    ?? ''
                                )
                            ) ?>"
                            placeholder="<?= adminEscape(
                                newsletterAdminFieldPlaceholder(
                                    $field
                                )
                            ) ?>"
                            <?= newsletterAdminIsUrl(
                                $field
                            )
                                ?
                                'inputmode="url"'
                                :
                                ''
                            ?>
                            autocomplete="off"
                        >


                        <?php if (
                            isset(
                                $fieldErrors[
                                    $field
                                ]
                            )
                        ): ?>

                            <div class="field-error">
                                <?= adminEscape(
                                    $fieldErrors[
                                        $field
                                    ]
                                ) ?>
                            </div>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>


                <?php if (
                    $selectedTemplate !== ''
                ): ?>

                    <div class="schedule-box">

                        <h3>
                            Versandzeitpunkt
                        </h3>

                        <div class="radio-row">

                            <label>

                                <input
                                    type="radio"
                                    name="schedule_mode"
                                    value="next"
                                    <?= $scheduleMode === 'next' ? 'checked' : '' ?>
                                >

                                Nächstmöglich

                            </label>


                            <label>

                                <input
                                    type="radio"
                                    name="schedule_mode"
                                    value="scheduled"
                                    <?= $scheduleMode === 'scheduled' ? 'checked' : '' ?>
                                >

                                Zeitpunkt wählen

                            </label>

                        </div>


                        <div
                            class="schedule-fields"
                            id="schedule-fields"
                            <?= $scheduleMode === 'scheduled' ? '' : 'hidden' ?>
                        >

                            <div
                                class="<?= isset(
                                    $fieldErrors[
                                        'schedule_date'
                                    ]
                                )
                                    ?
                                    'has-error'
                                    :
                                    ''
                                ?>"
                            >

                                <label for="schedule_date">
                                    Datum
                                </label>

                                <input
                                    type="date"
                                    id="schedule_date"
                                    name="schedule_date"
                                    value="<?= adminEscape(
                                        $scheduleDate
                                    ) ?>"
                                >


                                <?php if (
                                    isset(
                                        $fieldErrors[
                                            'schedule_date'
                                        ]
                                    )
                                ): ?>

                                    <div class="field-error">
                                        <?= adminEscape(
                                            $fieldErrors[
                                                'schedule_date'
                                            ]
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>


                            <div
                                class="<?= isset(
                                    $fieldErrors[
                                        'schedule_time'
                                    ]
                                )
                                    ?
                                    'has-error'
                                    :
                                    ''
                                ?>"
                            >

                                <label for="schedule_time">
                                    Uhrzeit
                                </label>

                                <input
                                    type="time"
                                    id="schedule_time"
                                    name="schedule_time"
                                    value="<?= adminEscape(
                                        $scheduleTime
                                    ) ?>"
                                    step="300"
                                >


                                <?php if (
                                    isset(
                                        $fieldErrors[
                                            'schedule_time'
                                        ]
                                    )
                                ): ?>

                                    <div class="field-error">
                                        <?= adminEscape(
                                            $fieldErrors[
                                                'schedule_time'
                                            ]
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="rich-help">
                            Der Worker läuft bei dir alle 5 Minuten.
                            Der Versand startet beim ersten Worker-Lauf
                            nach dem gewählten Zeitpunkt.
                        </div>

                    </div>


                    <div class="actions">

                        <button
                            class="button button-secondary"
                            type="submit"
                            name="action"
                            value="preview"
                        >
                            Vorschau anzeigen
                        </button>


                        <button
                            class="button button-secondary"
                            type="submit"
                            name="action"
                            value="test_send"
                        >
                            Testmail senden
                        </button>


                        <button
                            class="button"
                            type="submit"
                            name="action"
                            value="prepare_send"
                        >
                            Versand vorbereiten ·
                            <?= $subscriberCount ?>
                            Empfänger
                        </button>

                    </div>

                <?php endif; ?>


                <?php if (
                    $prepareSend
                ): ?>

                    <div class="confirm">

                        <strong>
                            Versand wirklich einplanen?
                        </strong>

                        <p>

                            Betreff:
                            „<?= adminEscape(
                                (string)(
                                    $formValues[
                                        'BETREFF'
                                    ]
                                    ?? ''
                                )
                            ) ?>“

                            <br>

                            Empfänger:
                            <?= $subscriberCount ?>
                            bestätigte Newsletter-Abonnenten.

                            <br>

                            Start:
                            <?= $scheduleMode === 'scheduled'
                                ?
                                adminEscape(
                                    $scheduleDate
                                    . ' '
                                    . $scheduleTime
                                    . ' Uhr'
                                )
                                :
                                'nächstmöglich'
                            ?>

                        </p>


                        <label for="send_confirmation">
                            Zur Bestätigung VERSENDEN eingeben
                        </label>

                        <input
                            id="send_confirmation"
                            name="send_confirmation"
                            autocomplete="off"
                            placeholder="VERSENDEN"
                        >


                        <div class="actions">

                            <button
                                class="button button-dark"
                                type="submit"
                                name="action"
                                value="queue_send"
                            >
                                Jetzt für den Versand einplanen
                            </button>

                        </div>

                    </div>

                <?php endif; ?>

            </form>

        </section>


        <section class="card preview-card">

            <div class="preview-header">

                <div class="preview-title">
                    E-Mail-Vorschau
                </div>

                <div class="preview-note">
                    nur vollständige aktuelle Vorlage
                </div>

            </div>


            <?php if (
                $previewHtml !== null
            ): ?>

                <iframe
                    title="Newsletter Vorschau"
                    sandbox
                    srcdoc="<?= adminEscape(
                        $previewHtml
                    ) ?>"
                ></iframe>

            <?php else: ?>

                <div class="empty-preview">

                    <div>

                        Fülle die aktuell ausgewählte Vorlage
                        vollständig aus und klicke auf

                        <strong>
                            „Vorschau anzeigen“
                        </strong>.

                    </div>

                </div>

            <?php endif; ?>

        </section>

    </div>


    <section class="card stats">

        <div class="stats-head">

            <h2>
                Versandstatus
            </h2>

            <div
                class="count-badge"
                id="subscriber-count"
            >
                <?= $subscriberCount ?>
                Abonnenten
            </div>

        </div>


        <?php if (
            !$recentCampaigns
        ): ?>

            <div class="muted">
                Noch kein Newsletter-Versand angelegt.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table
                    class="history"
                    id="campaign-table"
                >

                    <thead>

                    <tr>

                        <th>#</th>

                        <th>
                            Betreff
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Geplant für
                        </th>

                        <th>
                            Gesendet
                        </th>

                        <th>
                            Offen
                        </th>

                        <th>
                            Übersprungen
                        </th>

                        <th>
                            Fehler
                        </th>

                        <th>
                            Gesamt
                        </th>

                        <th>
                            Aktion
                        </th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php foreach (
                        $recentCampaigns
                        as
                        $campaign
                    ): ?>

                        <?php

                        $openCount =
                            max(
                                0,
                                (int)$campaign[
                                    'total_count'
                                ]
                                -
                                (int)$campaign[
                                    'sent_count'
                                ]
                                -
                                (int)$campaign[
                                    'skipped_count'
                                ]
                                -
                                (int)$campaign[
                                    'failed_count'
                                ]
                                -
                                (int)$campaign[
                                    'cancelled_count'
                                ]
                            );

                        ?>


                        <tr
                            data-campaign-id="<?= (int)$campaign['id'] ?>"
                        >

                            <td>
                                <?= (int)$campaign['id'] ?>
                            </td>

                            <td>
                                <?= adminEscape(
                                    (string)$campaign['subject']
                                ) ?>
                            </td>

                            <td
                                class="status <?= adminEscape(
                                    (string)$campaign['status']
                                ) ?>"
                                data-role="status"
                            >
                                <?= adminEscape(
                                    newsletterAdminStatusLabel(
                                        (string)$campaign['status']
                                    )
                                ) ?>
                            </td>

                            <td data-role="scheduled">
                                <?= adminEscape(
                                    newsletterAdminFormatUtc(
                                        $campaign['scheduled_at']
                                        !== null
                                        ?
                                        (string)$campaign['scheduled_at']
                                        :
                                        null
                                    )
                                ) ?>
                            </td>

                            <td data-role="sent">
                                <?= (int)$campaign['sent_count'] ?>
                            </td>

                            <td data-role="open">
                                <?= $openCount ?>
                            </td>

                            <td data-role="skipped">
                                <?= (int)$campaign['skipped_count'] ?>
                            </td>

                            <td data-role="failed">
                                <?= (int)$campaign['failed_count'] ?>
                            </td>

                            <td>
                                <?= (int)$campaign['total_count'] ?>
                            </td>

                            <td>

                                <?php if (
                                    in_array(
                                        (string)$campaign['status'],
                                        [
                                            'queued',
                                            'sending',
                                        ],
                                        true
                                    )
                                ): ?>

                                    <form
                                        method="post"
                                        class="cancel-form"
                                        onsubmit="return confirm('Diesen Newsletter wirklich stoppen? Bereits versendete E-Mails können nicht zurückgeholt werden.');"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= adminEscape(
                                                $csrf
                                            ) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="cancel_campaign"
                                        >

                                        <input
                                            type="hidden"
                                            name="campaign_id"
                                            value="<?= (int)$campaign['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="return_template"
                                            value="<?= adminEscape(
                                                $selectedTemplate
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="cancel-button"
                                        >
                                            Versand abbrechen
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="status-note">
                                        –
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</div>


<script>

(function(){

    var form =
        document.getElementById(
            'newsletter-form'
        );

    var templateSelector =
        document.getElementById(
            'template-selector'
        );

    var selectedTemplate =
        <?= json_encode(
            $selectedTemplate,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        ) ?>;

    var draftKey =
        'gdmm-newsletter-draft:'
        +
        selectedTemplate;

    var restoring =
        false;

    var editor =
        document.getElementById(
            'rich-editor'
        );

    var richHidden =
        document.getElementById(
            'INHALT'
        );


    function syncRich(){

        if(
            editor
            &&
            richHidden
        ){

            richHidden.value =
                editor.innerHTML.trim();
        }
    }


    function syncRhythm(
        wrapper
    ){

        var choice =
            wrapper.querySelector(
                '[data-rhythm-choice]'
            );

        var custom =
            wrapper.querySelector(
                '[data-rhythm-custom]'
            );

        var hidden =
            wrapper.querySelector(
                '[data-rhythm-hidden]'
            );


        if(
            !choice
            ||
            !custom
            ||
            !hidden
        ){
            return;
        }


        custom.hidden =
            choice.value
            !==
            '__custom__';


        hidden.value =
            choice.value
            ===
            '__custom__'
            ?
            custom.value.trim()
            :
            choice.value;
    }


    function syncTimeRange(
        wrapper
    ){

        var startSelect =
            wrapper.querySelector(
                '[data-time-start-choice]'
            );

        var endSelect =
            wrapper.querySelector(
                '[data-time-end-choice]'
            );

        var hidden =
            wrapper.querySelector(
                '[data-time-hidden]'
            );

        if(
            !startSelect
            ||
            !endSelect
            ||
            !hidden
        ){
            return;
        }

        var start =
            startSelect.value;

        var end =
            endSelect.value;

        hidden.value =
            (
                start
                &&
                end
            )
            ?
            start
            +
            '–'
            +
            end
            +
            ' Uhr'
            :
            '';
    }


    function saveDraft(){

        if(
            !form
            ||
            restoring
        ){
            return;
        }


        syncRich();


        document
        .querySelectorAll(
            '[data-rhythm-wrap]'
        )
        .forEach(
            syncRhythm
        );


        document
        .querySelectorAll(
            '[data-time-range]'
        )
        .forEach(
            syncTimeRange
        );


        var data = {};


        new FormData(
            form
        )
        .forEach(
            function(
                value,
                key
            ){

                if(
                    key
                    !==
                    'csrf'
                    &&
                    key
                    !==
                    'action'
                    &&
                    key
                    !==
                    'send_confirmation'
                ){

                    data[key] =
                        value;
                }
            }
        );


        data.__course_open = {};


        document
        .querySelectorAll(
            'details[data-course]'
        )
        .forEach(
            function(
                details
            ){

                data.__course_open[
                    details.getAttribute(
                        'data-course'
                    )
                ]
                =
                details.open;
            }
        );


        try{

            localStorage.setItem(
                draftKey,
                JSON.stringify(
                    data
                )
            );

        }catch(
            error
        ){}
    }


    function restoreDraft(){

        if(
            !form
            ||
            <?= $_SERVER['REQUEST_METHOD'] === 'POST' ? 'true' : 'false' ?>
        ){
            return;
        }


        var raw = null;


        try{

            raw =
                localStorage.getItem(
                    draftKey
                );

        }catch(
            error
        ){}


        if(
            !raw
        ){
            return;
        }


        var data = null;


        try{

            data =
                JSON.parse(
                    raw
                );

        }catch(
            error
        ){

            return;
        }


        restoring =
            true;


        Object
        .keys(
            data
        )
        .forEach(
            function(
                key
            ){

                if(
                    key
                    ===
                    '__course_open'
                ){
                    return;
                }


                form
                .querySelectorAll(
                    '[name="'
                    +
                    CSS.escape(
                        key
                    )
                    +
                    '"]'
                )
                .forEach(
                    function(
                        element
                    ){

                        if(
                            element.type
                            ===
                            'radio'
                        ){

                            element.checked =
                                element.value
                                ===
                                data[key];


                        }else{

                            var value =
                                data[key];

                            if(
                                element.hasAttribute(
                                    'data-course-date'
                                )
                                &&
                                typeof value
                                ===
                                'string'
                            ){

                                var germanDate =
                                    /^(\d{2})\.(\d{2})\.(\d{4})$/
                                    .exec(
                                        value
                                    );

                                if(
                                    germanDate
                                ){

                                    value =
                                        germanDate[3]
                                        +
                                        '-'
                                        +
                                        germanDate[2]
                                        +
                                        '-'
                                        +
                                        germanDate[1];
                                }
                            }

                            element.value =
                                value;
                        }
                    }
                );
            }
        );


        if(
            editor
            &&
            richHidden
            &&
            data.INHALT
            !==
            undefined
        ){

            richHidden.value =
                data.INHALT;

            editor.innerHTML =
                data.INHALT;
        }


        if(
            data.__course_open
        ){

            document
            .querySelectorAll(
                'details[data-course]'
            )
            .forEach(
                function(
                    details
                ){

                    var number =
                        details.getAttribute(
                            'data-course'
                        );


                    if(
                        data.__course_open[
                            number
                        ]
                        !==
                        undefined
                    ){

                        details.open =
                            !!data.__course_open[
                                number
                            ];
                    }
                }
            );
        }


        document
        .querySelectorAll(
            '[data-rhythm-wrap]'
        )
        .forEach(
            syncRhythm
        );


        document
        .querySelectorAll(
            '[data-time-range]'
        )
        .forEach(
            syncTimeRange
        );


        var scheduled =
            form.querySelector(
                'input[name="schedule_mode"]:checked'
            );

        var scheduleFields =
            document.getElementById(
                'schedule-fields'
            );


        if(
            scheduleFields
            &&
            scheduled
        ){

            scheduleFields.hidden =
                scheduled.value
                !==
                'scheduled';
        }


        restoring =
            false;
    }


    /*
    |--------------------------------------------------------------------------
    | RICH TEXT
    |--------------------------------------------------------------------------
    */

    if(
        editor
        &&
        richHidden
    ){

        document
        .querySelectorAll(
            '[data-rich-command]'
        )
        .forEach(
            function(
                button
            ){

                button
                .addEventListener(
                    'mousedown',
                    function(
                        event
                    ){

                        event.preventDefault();
                    }
                );


                button
                .addEventListener(
                    'click',
                    function(){

                        editor.focus();

                        document.execCommand(
                            button.getAttribute(
                                'data-rich-command'
                            ),
                            false,
                            null
                        );

                        syncRich();

                        saveDraft();
                    }
                );
            }
        );


        var formulaButton =
            document.getElementById(
                'formula-button'
            );


        if(
            formulaButton
        ){

            formulaButton
                .addEventListener(
                    'mousedown',
                    function(
                        event
                    ){

                        event.preventDefault();
                    }
                );


            formulaButton
                .addEventListener(
                    'click',
                    function(){

                        var selection =
                            window.getSelection();

                        var selectedText =
                            selection
                            ?
                            selection
                            .toString()
                            .trim()
                            :
                            '';


                        var formula =
                            window.prompt(
                                'Formel eingeben, z. B. v = s / t',
                                selectedText
                            );


                        if(
                            formula === null
                            ||
                            !formula.trim()
                        ){
                            return;
                        }


                        editor.focus();


                        var span =
                            document.createElement(
                                'span'
                            );

                        span.className =
                            'gdmm-formula';

                        span.textContent =
                            formula.trim();


                        var range =
                            selection
                            &&
                            selection.rangeCount
                            ?
                            selection.getRangeAt(
                                0
                            )
                            :
                            null;


                        if(
                            range
                            &&
                            editor.contains(
                                range.commonAncestorContainer
                            )
                        ){

                            range.deleteContents();

                            range.insertNode(
                                span
                            );

                            range.setStartAfter(
                                span
                            );

                            range.collapse(
                                true
                            );

                            selection.removeAllRanges();

                            selection.addRange(
                                range
                            );


                        }else{

                            editor.appendChild(
                                span
                            );
                        }


                        syncRich();

                        saveDraft();
                    }
                );
        }


        editor
            .addEventListener(
                'input',
                function(){

                    syncRich();

                    saveDraft();
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | RHYTHMUS
    |--------------------------------------------------------------------------
    */

    document
    .querySelectorAll(
        '[data-rhythm-wrap]'
    )
    .forEach(
        function(
            wrapper
        ){

            var choice =
                wrapper.querySelector(
                    '[data-rhythm-choice]'
                );

            var custom =
                wrapper.querySelector(
                    '[data-rhythm-custom]'
                );


            if(
                choice
            ){

                choice
                    .addEventListener(
                        'change',
                        function(){

                            syncRhythm(
                                wrapper
                            );

                            saveDraft();
                        }
                    );
            }


            if(
                custom
            ){

                custom
                    .addEventListener(
                        'input',
                        function(){

                            syncRhythm(
                                wrapper
                            );

                            saveDraft();
                        }
                    );
            }


            syncRhythm(
                wrapper
            );
        }
    );


/*
|--------------------------------------------------------------------------
| UHRZEITEN
|--------------------------------------------------------------------------
|
| Start- und Endzeit sind voneinander unabhängig.
| Keine automatische Berechnung der Endzeit.
|
*/

document
.querySelectorAll(
    '[data-time-range]'
)
.forEach(
    function(
        wrapper
    ){

        var startSelect =
            wrapper.querySelector(
                '[data-time-start-choice]'
            );

        var endSelect =
            wrapper.querySelector(
                '[data-time-end-choice]'
            );

        if(
            !startSelect
            ||
            !endSelect
        ){
            return;
        }

        startSelect
            .addEventListener(
                'change',
                function(){

                    syncTimeRange(
                        wrapper
                    );

                    saveDraft();
                }
            );

        endSelect
            .addEventListener(
                'change',
                function(){

                    syncTimeRange(
                        wrapper
                    );

                    saveDraft();
                }
            );

        syncTimeRange(
            wrapper
        );
    }
);


    /*
    |--------------------------------------------------------------------------
    | KURS 2 PREISE
    |--------------------------------------------------------------------------
    */

    var course2 =
        document.querySelector(
            'details[data-course="2"]'
        );

    var course2Price =
        document.querySelector(
            '[name="KURS_2_PREIS"]'
        );

    var course2OldPrice =
        document.querySelector(
            '[name="KURS_2_PREIS_ALT"]'
        );


    if(
        course2
    ){

        course2
        .querySelectorAll(
            'input,select'
        )
        .forEach(
            function(
                element
            ){

                function activate(){

                    if(
                        String(
                            element.value
                            ||
                            ''
                        )
                        .trim()
                    ){

                        if(
                            course2Price
                            &&
                            !course2Price.value
                        ){

                            course2Price.value =
                                '890 €';
                        }


                        if(
                            course2OldPrice
                            &&
                            !course2OldPrice.value
                        ){

                            course2OldPrice.value =
                                '980 €';
                        }
                    }
                }


                element
                    .addEventListener(
                        'change',
                        activate
                    );

                element
                    .addEventListener(
                        'input',
                        activate
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERSANDZEIT
    |--------------------------------------------------------------------------
    */

    var scheduleFields =
        document.getElementById(
            'schedule-fields'
        );


    document
    .querySelectorAll(
        'input[name="schedule_mode"]'
    )
    .forEach(
        function(
            radio
        ){

            radio
                .addEventListener(
                    'change',
                    function(){

                        if(
                            scheduleFields
                        ){

                            scheduleFields.hidden =
                                this.value
                                !==
                                'scheduled';
                        }

                        saveDraft();
                    }
                );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | AUF-/ZUKLAPPSTATUS
    |--------------------------------------------------------------------------
    */

    document
    .querySelectorAll(
        'details[data-course]'
    )
    .forEach(
        function(
            details
        ){

            details
                .addEventListener(
                    'toggle',
                    saveDraft
                );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ENTWURF LADEN
    |--------------------------------------------------------------------------
    */

    restoreDraft();


    if(
        form
    ){

        form
            .addEventListener(
                'input',
                saveDraft
            );

        form
            .addEventListener(
                'change',
                saveDraft
            );


        form
            .addEventListener(
                'submit',
                function(){

                    syncRich();


                    document
                    .querySelectorAll(
                        '[data-rhythm-wrap]'
                    )
                    .forEach(
                        syncRhythm
                    );


                    document
                    .querySelectorAll(
                        '[data-time-range]'
                    )
                    .forEach(
                        syncTimeRange
                    );


                    saveDraft();
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TEMPLATE WECHSEL
    |--------------------------------------------------------------------------
    */

    if(
        templateSelector
    ){

        templateSelector
            .addEventListener(
                'change',
                function(){

                    saveDraft();

                    window.location.href =
                        '/intern/newsletter.php?template='
                        +
                        encodeURIComponent(
                            templateSelector.value
                        );
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | FORMULAR LEEREN
    |--------------------------------------------------------------------------
    */

    var clearButton =
        document.getElementById(
            'clear-form'
        );


    if(
        clearButton
    ){

        clearButton
            .addEventListener(
                'click',
                function(){

                    if(
                        !confirm(
                            'Das aktuelle Formular wirklich leeren? Die andere Vorlage bleibt unverändert.'
                        )
                    ){
                        return;
                    }


                    try{

                        localStorage.removeItem(
                            draftKey
                        );

                    }catch(
                        error
                    ){}


                    window.location.href =
                        '/intern/newsletter.php?template='
                        +
                        encodeURIComponent(
                            selectedTemplate
                        );
                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS AUTOMATISCH AKTUALISIEREN
    |--------------------------------------------------------------------------
    */

    function refreshCampaignStatus(){

        fetch(
            '/intern/newsletter.php?status_json=1',
            {
                credentials:
                    'same-origin',

                cache:
                    'no-store'
            }
        )
        .then(
            function(
                response
            ){

                return response.json();
            }
        )
        .then(
            function(
                payload
            ){

                if(
                    !payload
                    ||
                    !payload.success
                ){
                    return;
                }


                var badge =
                    document.getElementById(
                        'subscriber-count'
                    );


                if(
                    badge
                ){

                    badge.textContent =
                        payload.subscriber_count
                        +
                        ' Abonnenten';
                }


                (
                    payload.campaigns
                    ||
                    []
                )
                .forEach(
                    function(
                        campaign
                    ){

                        var row =
                            document.querySelector(
                                'tr[data-campaign-id="'
                                +
                                campaign.id
                                +
                                '"]'
                            );


                        if(
                            !row
                        ){
                            return;
                        }


                        var status =
                            row.querySelector(
                                '[data-role="status"]'
                            );


                        if(
                            status
                        ){

                            status.textContent =
                                campaign.status_label;

                            status.className =
                                'status '
                                +
                                campaign.status;
                        }


                        var scheduled =
                            row.querySelector(
                                '[data-role="scheduled"]'
                            );

                        if(
                            scheduled
                        ){

                            scheduled.textContent =
                                campaign.scheduled_label;
                        }


                        var sent =
                            row.querySelector(
                                '[data-role="sent"]'
                            );

                        if(
                            sent
                        ){

                            sent.textContent =
                                campaign.sent_count;
                        }


                        var open =
                            row.querySelector(
                                '[data-role="open"]'
                            );

                        if(
                            open
                        ){

                            open.textContent =
                                campaign.open_count;
                        }


                        var skipped =
                            row.querySelector(
                                '[data-role="skipped"]'
                            );

                        if(
                            skipped
                        ){

                            skipped.textContent =
                                campaign.skipped_count;
                        }


                        var failed =
                            row.querySelector(
                                '[data-role="failed"]'
                            );

                        if(
                            failed
                        ){

                            failed.textContent =
                                campaign.failed_count;
                        }


                        if(
                            campaign.status
                            ===
                            'completed'
                            ||
                            campaign.status
                            ===
                            'cancelled'
                        ){

                            var cancelForm =
                                row.querySelector(
                                    '.cancel-form'
                                );


                            if(
                                cancelForm
                            ){

                                cancelForm.replaceWith(
                                    document.createTextNode(
                                        '–'
                                    )
                                );
                            }
                        }
                    }
                );
            }
        )
        .catch(
            function(){}
        );
    }


    window.setInterval(
        refreshCampaignStatus,
        15000
    );

    /*
    |--------------------------------------------------------------------------
    | FEHLERMARKIERUNG BEIM BEARBEITEN ENTFERNEN
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'focusin',
        function(event){

            var field =
                event.target.closest(
                    '.field.has-error'
                );

            if(field){

                field.classList.remove(
                    'has-error'
                );
            }
        }
    );


})();

</script>

</body>

</html>