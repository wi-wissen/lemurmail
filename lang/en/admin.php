<?php

return [
    'nav' => [
        'users' => 'Teacher accounts',
        'domains' => 'School domains',
        'freemail' => 'Blocked domains',
        'settings' => 'Settings',
    ],

    'status' => [
        'pending' => 'pending',
        'active' => 'active',
        'rejected' => 'rejected',
        'blocked' => 'blocked',
    ],

    'super_admin' => 'Super admin',

    'errors' => [
        'invalid_transition' => 'This action is not possible for the status of this account.',
        'super_admin_block' => 'Super admins cannot be blocked.',
        'domain_required' => 'Please enter a domain.',
        'domain_invalid' => 'This is not a valid domain, e.g. school.example.org.',
        'pattern_invalid' => 'This is not a valid regular expression (at most 200 characters).',
    ],

    'users' => [
        'title' => 'Teacher accounts',
        'pending_summary' => '{0} No accounts are waiting for approval.|{1} :count account is waiting for approval.|[2,*] :count accounts are waiting for approval.',
        'col_email' => 'Email',
        'col_status' => 'Status',
        'col_addresses' => 'Addresses',
        'col_limit' => 'Address limit',
        'col_created' => 'Created',
        'col_actions' => 'Actions',
        'limit_label' => 'Own address limit, empty = default',
        'empty' => 'No teachers yet.',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'block' => 'Block',
        'unblock' => 'Unblock',
        'limit_save' => 'Set limit',
        'approved' => 'Account approved. The teacher has been notified by email.',
        'rejected' => 'Registration rejected. The teacher has been notified by email.',
        'blocked' => 'Account blocked.',
        'unblocked' => 'Account unblocked.',
        'limit_saved' => 'Address limit saved.',
    ],

    'settings' => [
        'title' => 'Global default address limit',
        'label' => 'Default per teacher',
        'hint' => 'Applies to all teachers without their own limit.',
        'saved' => 'Default address limit saved.',
    ],

    'domains' => [
        'organization_title' => 'School domains',
        'explanation' => 'Anyone registering with an address that matches an entry is approved at once, everyone else waits for your approval. Without a pattern the entry applies to every address of the domain. With a pattern (regular expression) only to addresses whose name before the @ fully matches the pattern, case-insensitive. Example: [a-z]+\.[a-z]+ matches max.mustermann, but not max.mustermann7.',
        'domain' => 'Domain',
        'domain_placeholder' => 'school.example.org',
        'pattern' => 'Pattern for the name before the @ (optional)',
        'pattern_placeholder' => '[a-z]+\.[a-z]+',
        'pattern_all' => 'all addresses',
        'add' => 'Add',
        'remove' => 'Remove',
        'remove_entry' => 'Remove entry :entry',
        'none_organization' => 'No school domains yet.',
        'saved' => 'Entry saved.',
        'removed' => 'Entry removed.',
    ],

    'freemail' => [
        'title' => 'Blocked domains',
        'hint' => 'Freemail and disposable domains that cannot be used to register.',
        'none' => 'No domains blocked.',
    ],

    'mail' => [
        'approved_subject' => 'Your Lemur Mail account has been approved',
        'rejected_subject' => 'Your registration with Lemur Mail',
        'greeting' => 'Hello,',
        'approved_line' => 'Your account has been approved. You can sign in now.',
        'approved_action' => 'Go to sign in',
        'rejected_line' => 'Unfortunately, your registration was not approved.',
    ],
];
