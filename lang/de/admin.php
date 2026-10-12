<?php

return [
    'nav' => [
        'users' => 'Lehrer:innen-Konten',
        'domains' => 'Schul-Domains',
        'freemail' => 'Gesperrte Domains',
        'settings' => 'Einstellungen',
    ],

    'status' => [
        'pending' => 'wartet',
        'active' => 'aktiv',
        'rejected' => 'abgelehnt',
        'blocked' => 'gesperrt',
    ],

    'super_admin' => 'Super-Admin',

    'errors' => [
        'invalid_transition' => 'Diese Aktion ist für den Status dieses Kontos nicht möglich.',
        'super_admin_block' => 'Super-Admins können nicht gesperrt werden.',
        'domain_required' => 'Bitte eine Domain eingeben.',
        'domain_invalid' => 'Das ist keine gültige Domain, z. B. schule.example.org.',
        'pattern_invalid' => 'Das ist kein gültiger regulärer Ausdruck (höchstens 200 Zeichen).',
    ],

    'users' => [
        'title' => 'Lehrer:innen-Konten',
        'pending_summary' => '{0} Keine Konten warten auf Freigabe.|{1} :count Konto wartet auf Freigabe.|[2,*] :count Konten warten auf Freigabe.',
        'col_email' => 'E-Mail',
        'col_status' => 'Status',
        'col_addresses' => 'Adressen',
        'col_limit' => 'Adresslimit',
        'col_created' => 'Angelegt',
        'col_actions' => 'Aktionen',
        'limit_label' => 'Eigenes Adresslimit, leer = Standard',
        'empty' => 'Noch keine Lehrer:innen angelegt.',
        'approve' => 'Freigeben',
        'reject' => 'Ablehnen',
        'block' => 'Sperren',
        'unblock' => 'Entsperren',
        'limit_save' => 'Limit setzen',
        'approved' => 'Konto freigegeben. Die Lehrer:in wurde per E-Mail informiert.',
        'rejected' => 'Anmeldung abgelehnt. Die Lehrer:in wurde per E-Mail informiert.',
        'blocked' => 'Konto gesperrt.',
        'unblocked' => 'Konto entsperrt.',
        'limit_saved' => 'Adresslimit gespeichert.',
    ],

    'settings' => [
        'title' => 'Globales Standard-Adresslimit',
        'label' => 'Standard je Lehrer:in',
        'hint' => 'Gilt für alle Lehrer:innen ohne eigenes Limit.',
        'saved' => 'Standard-Adresslimit gespeichert.',
    ],

    'domains' => [
        'organization_title' => 'Schul-Domains',
        'explanation' => 'Wer sich mit einer Adresse registriert, die zu einem Eintrag passt, wird sofort freigeschaltet, alle anderen warten auf deine Freigabe. Ohne Muster gilt der Eintrag für jede Adresse der Domain. Mit Muster (regulärer Ausdruck) nur für Adressen, deren Name vor dem @ vollständig zum Muster passt, Groß-/Kleinschreibung egal. Beispiel: [a-z]+\.[a-z]+ passt auf max.mustermann, aber nicht auf max.mustermann7.',
        'domain' => 'Domain',
        'domain_placeholder' => 'schule.example.org',
        'pattern' => 'Muster für den Namen vor dem @ (optional)',
        'pattern_placeholder' => '[a-z]+\.[a-z]+',
        'pattern_all' => 'alle Adressen',
        'add' => 'Hinzufügen',
        'remove' => 'Entfernen',
        'remove_entry' => 'Eintrag :entry entfernen',
        'none_organization' => 'Keine Schul-Domains hinterlegt.',
        'saved' => 'Eintrag gespeichert.',
        'removed' => 'Eintrag entfernt.',
    ],

    'freemail' => [
        'title' => 'Gesperrte Domains',
        'hint' => 'Freemail- und Wegwerf-Domains, für die keine Registrierung möglich ist.',
        'none' => 'Keine Domains gesperrt.',
    ],

    'mail' => [
        'approved_subject' => 'Dein Konto bei Lemur Mail ist freigegeben',
        'rejected_subject' => 'Deine Anmeldung bei Lemur Mail',
        'greeting' => 'Hallo,',
        'approved_line' => 'Dein Konto wurde freigegeben. Du kannst dich jetzt anmelden.',
        'approved_action' => 'Zur Anmeldung',
        'rejected_line' => 'Deine Anmeldung wurde leider nicht freigegeben.',
    ],
];
