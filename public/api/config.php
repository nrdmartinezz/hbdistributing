<?php
/**
 * Mail routing deployed with the site. No secrets belong here.
 * ~/private/site-mail.php and config.local.php override these keys.
 */
return [
    'notify_to' => 'info@hbdistributing.com',
    'notify_bcc' => ['nate@webpro.com', 'verifybu@webpro.com'],
    'from_email' => 'info@hbdistributing.com',
    'from_name' => 'Highland Breeze Distributing',
    'site_url' => 'https://www.hbdistributing.com',
    'site_phone' => '+1 (704) 282-2366',
    'site_phone_href' => '+17042822366',
    'recaptcha_site_key' => '6LeSt8stAAAAAECAC7v5zq8xauIXSMiBBa5gn3A5',
    'recaptcha_project_id' => 'hbdistributing',
    'forms' => [
        'contact' => [
            'subject' => 'New enquiry — Highland Breeze Distributing',
            'notification' => 'notification-contact.html',
        ],
        'sourcing' => [
            'subject' => 'Strategic sourcing request — Highland Breeze Distributing',
            'notification' => 'notification-sourcing.html',
        ],
    ],
];
