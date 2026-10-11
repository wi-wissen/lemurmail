<?php

return [
    'nav' => [
        'users' => 'Teacher accounts',
        'domains' => 'Domains',
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
        'default_limit' => 'Default (:n)',
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
        'organization_hint' => 'Registrations with these domains are approved automatically.',
        'freemail_title' => 'Blocked domains',
        'freemail_hint' => 'Freemail and disposable domains that cannot be used to register.',
        'domain' => 'Domain',
        'domain_placeholder' => 'school.example.org',
        'add' => 'Add',
        'remove' => 'Remove',
        'none_organization' => 'No school domains yet.',
        'none_freemail' => 'No domains blocked.',
        'saved' => 'Domain saved.',
        'removed' => 'Domain removed.',
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
