<?php
/**
 * Ethnoir Contact Configuration
 * 
 * SECURITY WARNING: 
 * Do NOT place this file inside the `public_html` directory if possible.
 * Place it one level above (e.g., in `/home/u123456789/domains/ethnoir.com/`).
 * If you must place it inside `public_html`, ensure your `.htaccess` denies access to it.
 */

return [
    // The email address where inquiries should be sent.
    'recipient_email' => 'omichael@ethnoirconsulting.com',
    
    // The email address the server will use to SEND the email.
    // This MUST be an address authorized by your Hostinger hosting account.
    // Do NOT use the user's email here, otherwise it will be marked as spam.
    'sender_email' => 'omichael@ethnoirconsulting.com',
    
    // SMTP Configuration for Hostinger
    // Reference: https://support.hostinger.com/en/articles/1583249-how-to-set-up-hostinger-email-on-devices
    'smtp_host' => 'smtp.hostinger.com',
    'smtp_port' => 465,
    'smtp_user' => 'omichael@ethnoirconsulting.com',
    'smtp_pass' => 'YOUR_SECURE_PASSWORD_HERE',
    
    // Path to the PHPMailer src folder.
    // Download PHPMailer and place the `src` folder here.
    // If you place it next to this config file, it would be __DIR__ . '/PHPMailer/src'
    'phpmailer_path' => __DIR__ . '/PHPMailer/src',
];
