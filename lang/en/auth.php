<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'freemail_not_allowed' => 'Addresses from providers like gmail.com are not allowed. Please use your work or school address.',
    'own_domain_not_allowed' => 'This address belongs to Lemur Mail itself. Please use your work or school address.',

    'email' => 'Email address',
    'password_label' => 'Password',
    'password_confirmation' => 'Repeat password',
    'current_password' => 'Current password',
    'new_password' => 'New password',

    'register_title' => 'Register as a teacher',
    'register_intro' => 'Please use a work or school email address, for example from your school or organisation.',
    'register_submit' => 'Register',
    'register_login_link' => 'Already registered? Log in',
    'password_hint' => 'At least 10 characters.',
    'hint_approved' => 'Your domain is approved. Your account is active as soon as you confirm the email.',
    'hint_unknown' => 'Your domain is not known yet. After you confirm the email, an admin checks your request.',

    'login_title' => 'Log in',
    'login_intro' => 'Log in with your work email address and your password.',
    'login_submit' => 'Log in',
    'remember_me' => 'Stay logged in',
    'forgot_password_link' => 'Forgot your password?',
    'register_link' => 'No account yet? Register now',

    'forgot_title' => 'Forgot your password?',
    'forgot_intro' => 'Enter your email address. We will send you a link to set a new password.',
    'forgot_submit' => 'Send link',

    'reset_title' => 'Set a new password',
    'reset_submit' => 'Save password',

    'verify_title' => 'Please confirm your email address',
    'verify_intro' => 'We sent you a confirmation link. Click it to activate your account. If the email did not arrive, we can send it again.',
    'verify_sent' => 'We sent you a new confirmation link.',
    'verify_resend' => 'Send confirmation email again',

    'confirm_title' => 'Confirm your password',
    'confirm_intro' => 'This area is protected. Please confirm your password to continue.',
    'confirm_submit' => 'Confirm',

    'approval_mail_subject' => 'A new teacher is waiting for approval',
    'approval_mail_line' => 'The address :email has confirmed its email and is waiting for approval.',
    'approval_mail_action' => 'Open user management',

    'cli_admin_exists' => 'An account for :email already exists.',
    'cli_password_required' => 'Without an interactive terminal the password must be given with --password.',
    'cli_password_prompt' => 'Password for the admin (at least 10 characters)',
    'cli_admin_created' => 'Super admin :email has been created.',

];
