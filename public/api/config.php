<?php
/**
 * Mail routing deployed with the site. No secrets belong here.
 * ~/private/site-mail.php and config.local.php override these keys.
 */
return [
    'notify_to' => 'TWK@hbdistributing.com',
    'notify_bcc' => ['nate@webpro.com', 'verifybu@webpro.com'],
    'from_email' => 'info@hbdistributing.com',
    'from_name' => 'HB Distributing',
    'forms' => [
        'contact' => [
            'subject' => 'New enquiry — HB Distributing',
            'notification' => 'notification-contact.html',
        ],
    ],
];
