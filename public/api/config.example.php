<?php
/**
 * Copy to config.local.php for local testing, or to ~/private/site-mail.php
 * on the server (outside public_html). Rename the private file per client project
 * (e.g. peninsula-pavers-mail.php) and update the path in lib/mailer.php.
 * Never commit real credentials.
 *
 * Mail transport: uses PHP mail() by default (like WordPress). Set smtp_host, smtp_user,
 * and smtp_pass to switch to authenticated SMTP — useful when mail() deliverability is poor.
 */
return [
    'recaptcha_site_key' => '6LeSt8stAAAAAECAC7v5zq8xauIXSMiBBa5gn3A5',
    // Enterprise assessment. Leave blank to fall back to recaptcha_secret.
    'recaptcha_project_id' => 'hbdistributing',
    'recaptcha_api_key' => '',
    'recaptcha_secret' => 'YOUR_RECAPTCHA_SECRET_KEY',
    'notify_to' => 'info@hbdistributing.com',
    'notify_bcc' => ['nate@webpro.com', 'verifybu@webpro.com'],
    'from_email' => 'info@hbdistributing.com',
    'from_name' => 'Highland Breeze Distributing',
    'site_url' => 'https://www.hbdistributing.com',
    'site_phone' => '+1 (704) 282-2366',
    'site_phone_href' => '+17042822366',
    'timezone' => 'America/New_York',

    // Optional SMTP — leave blank to use PHP mail() on the host.
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_user' => '',
    'smtp_pass' => '',

    'forms' => [
        'contact' => [
            'subject' => 'New enquiry — Highland Breeze Distributing',
            'notification' => 'notification-contact.html',
            // Autoreply disabled by default — set send_autoreply => true to enable.
        ],
        'sourcing' => [
            'subject' => 'Strategic sourcing request — Highland Breeze Distributing',
            'notification' => 'notification-sourcing.html',
        ],
    ],

    'recaptcha_min_score' => 0.5,
    'rate_limit_seconds' => 60,
    'rate_limit_max' => 5,
];
