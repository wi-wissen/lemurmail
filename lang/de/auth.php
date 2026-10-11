<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentifizierung
    |--------------------------------------------------------------------------
    |
    | Meldungen rund um Anmeldung, Registrierung und Konto.
    |
    */

    'failed' => 'Diese Zugangsdaten stimmen nicht.',
    'password' => 'Das Passwort ist falsch.',
    'throttle' => 'Zu viele Anmeldeversuche. Bitte warte :seconds Sekunden und probiere es dann erneut.',

    'freemail_not_allowed' => 'Adressen von Anbietern wie gmail.com sind nicht erlaubt. Bitte nutze deine dienstliche E-Mail-Adresse.',
    'own_domain_not_allowed' => 'Diese Adresse gehört zu Lemur Mail selbst. Bitte nutze deine dienstliche E-Mail-Adresse.',

    'email' => 'E-Mail-Adresse',
    'password_label' => 'Passwort',
    'password_confirmation' => 'Passwort wiederholen',
    'current_password' => 'Aktuelles Passwort',
    'new_password' => 'Neues Passwort',

    'register_title' => 'Als Lehrer:in registrieren',
    'register_intro' => 'Bitte nutze eine dienstliche E-Mail-Adresse, zum Beispiel von deiner Schule oder Organisation.',
    'register_submit' => 'Registrieren',
    'register_login_link' => 'Schon registriert? Anmelden',
    'password_hint' => 'Mindestens 10 Zeichen.',
    'hint_approved' => 'Die Domain ist freigegeben. Dein Konto ist aktiv, sobald du deine E-Mail bestätigt hast.',
    'hint_unknown' => 'Die Domain ist noch unbekannt. Nach der Bestätigung prüft ein Admin deine Anfrage.',

    'login_title' => 'Anmelden',
    'login_intro' => 'Melde dich mit deiner dienstlichen E-Mail-Adresse und deinem Passwort an.',
    'login_submit' => 'Anmelden',
    'remember_me' => 'Angemeldet bleiben',
    'forgot_password_link' => 'Passwort vergessen?',
    'register_link' => 'Noch kein Konto? Jetzt registrieren',

    'forgot_title' => 'Passwort vergessen?',
    'forgot_intro' => 'Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort setzen kannst.',
    'forgot_submit' => 'Link senden',

    'reset_title' => 'Neues Passwort setzen',
    'reset_submit' => 'Passwort speichern',

    'verify_title' => 'Bitte bestätige deine E-Mail-Adresse',
    'verify_intro' => 'Wir haben dir einen Bestätigungslink geschickt. Klicke darauf, um dein Konto zu aktivieren. Ist keine Mail angekommen, schicken wir dir gern eine neue.',
    'verify_sent' => 'Wir haben dir einen neuen Bestätigungslink geschickt.',
    'verify_resend' => 'Bestätigungsmail erneut senden',

    'confirm_title' => 'Passwort bestätigen',
    'confirm_intro' => 'Dieser Bereich ist geschützt. Bitte bestätige dein Passwort, um weiterzumachen.',
    'confirm_submit' => 'Bestätigen',

    'approval_mail_subject' => 'Eine neue Lehrkraft wartet auf Freigabe',
    'approval_mail_line' => 'Die Adresse :email hat ihre E-Mail bestätigt und wartet auf die Freigabe.',
    'approval_mail_action' => 'Zur Benutzerverwaltung',

    'cli_admin_exists' => 'Für :email gibt es bereits ein Konto.',
    'cli_password_required' => 'Ohne interaktives Terminal muss das Passwort mit --password angegeben werden.',
    'cli_password_prompt' => 'Passwort für den Admin (mindestens 10 Zeichen)',
    'cli_admin_created' => 'Der Super-Admin :email wurde angelegt.',

];
