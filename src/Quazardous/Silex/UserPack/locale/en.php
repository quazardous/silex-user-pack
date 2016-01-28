<?php
return [
    'user' => [
        'login' => [
            'title' => 'Silex User Pack Login',
            'labels' => [
                'username' => 'Username',
                'password' => 'Password',
                'submit' => 'Login !',
                'register' => 'Register',
                'recover_password' => 'Password lost ?',
            ],            
         ],
        'register' => [
            'title' => 'Silex User Pack Register',
            'labels' => [
                'username' => 'Username',
                'email' => 'E-mail address',
                'password' => 'Password',
                'confirm_password' => 'Confirm password',
                'submit' => 'Register !',
                'cancel' => 'Cancel',
                'login' => 'Login',
                'recover_password' => 'Password lost ?',
            ],
        ],
        'register_complete' => [
            'title' => 'Silex User Pack Register Complete',
            'labels' => [
                'login' => 'Login',
            ],
            'texts' => [
                'require_email_verification' => 'Your registration is not complete. You will shortly receive an e-mail telling you how to complete your registration.',
                'email_verification' => 'Your registration is now complete. You can use the login link. You will shortly receive an e-mail to confirm your address.',
                'no_email_verification' => 'Your registration is now complete. You can use the login link.',
            ],
        ],
        'register_confirm' => [
            'title' => 'Silex User Pack Register Confirm',
            'labels' => [
                'login' => 'Login',
            ],
            'texts' => [
                'require_email_verification' => 'Your registration is now complete. You can use the login link.',
                'email_verification' => 'Your e-mail address is now verified.',
            ],
         ],
        'email_verification' => [
            'subject' => 'Registration is complete',
            'texts' => [
                'welcome' => 'Your registration is now complete.',
                'confirm_email' => 'You should confirm you e-mail address by clicking on the link below.',
                'copy_link' => 'If you cannot click on the link, please copy the following link in your browser:',
             ],
            'labels' => [
                'confirm_link' => 'Confirm my e-mail address',
            ],
        ],
        'require_email_verification' => [
            'subject' => 'Confirm your registration',
            'texts' => [
                'welcome' => 'Your registration is not complete. You must confirm your e-mail address.',
                'confirm_email' => 'You can confirm you e-mail address by clicking on the link below.',
                'copy_link' => 'If you cannot click on the link, please copy the following link in your browser:',
             ],
            'labels' => [
                'confirm_link' => 'Confirm my e-mail address',
             ],
        ],
        'email' => [
            'labels' => [
                'login_link' => 'Login',
            ],
        ],
        'recover_password' => [
            'title' => 'Silex User Pack Recover Password',
            'texts' => [
                'welcome' => 'If you have lost your password, complete the form below. You will receive an e-mail telling you how to change your password.',
            ],
            'messages' => [
                'sent' => 'Your request has been noted. You will receive an e-mail shortly.',
            ],
            'labels' => [
                'email' => 'E-mail address',
                'submit' => 'Recover password',
                'register' => 'Register',
                'login' => 'Login',
            ],
        ],
        'email_recover_password' => [
            'subject' => 'Change your password',
            'texts' => [
                'welcome' => 'You are receiving this e-mail because you have requested a password change. If it\'s not the case, you can safely ignore this e-mail.',
                'change_password' => 'To change your password, click on the link below.',
                'copy_link' => 'If you cannot click on the link, please copy the following link in your browser:',
            ],
            'labels' => [
                'email' => 'E-mail address',
                'submit' => 'Recover password',
                'change_password_link' => 'Change my password',
            ],
        ],
        'recover_password_confirm' => [
            'title' => 'Silex User Pack Recover Password Confirm',
            'texts' => [
                'welcome' => 'Change your password.',
            ],
            'labels' => [
                'password' => 'Password',
                'confirm_password' => 'Confirm password',
                'submit' => 'Change password !',
                'register' => 'Register',
                'login' => 'Login',
            ],
            'messages' => [
                'password_changed' => 'Your password has been changed.',
            ],
        ],
    ]
];