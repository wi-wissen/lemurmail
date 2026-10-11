<?php

return [
    'nav' => [
        'users' => 'Lehrer:innen-Konten',
        'domains' => 'Domains',
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
        'default_limit' => 'Standard (:n)',
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
        'organization_hint' => 'Registrierungen mit diesen Domains werden automatisch freigegeben.',
        'freemail_title' => 'Gesperrte Domains',
        'freemail_hint' => 'Freemail- und Wegwerf-Domains, für die keine Registrierung möglich ist.',
        'domain' => 'Domain',
        'domain_placeholder' => 'schule.example.org',
        'add' => 'Hinzufügen',
        'remove' => 'Entfernen',
        'none_organization' => 'Keine Schul-Domains hinterlegt.',
        'none_freemail' => 'Keine Domains gesperrt.',
        'saved' => 'Domain gespeichert.',
        'removed' => 'Domain entfernt.',
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
