<?php
/**
 * Mail routing deployed with the site. No secrets belong here.
 * ~/private/site-mail.php and config.local.php override these keys.
 */
return [
    'notify_to' => 'TWK@hbdistributing.com',
    'notify_bcc' => ['nate@webpro.com', 'verifybu@webpro.com'],
    'from_email' => 'TWK@hbdistributing.com',
    'from_name' => 'HB Distributing',
    'recaptcha_site_key' => '6LeSt8stAAAAAECAC7v5zq8xauIXSMiBBa5gn3A5',
    'recaptcha_project_id' => 'hbdistributing',
    'forms' => [
        'contact' => [
            'subject' => 'New enquiry — HB Distributing',
            'notification' => 'notification-contact.html',
        ],
        'sourcing' => [
            'subject' => 'Strategic sourcing request — HB Distributing',
            'notification' => 'notification-sourcing.html',
        ],
    ],
];
