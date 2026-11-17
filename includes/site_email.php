<?php
/**
 * Lecture centralisée de config/email.php (sans dépendre de Composer)
 */

if (!function_exists('site_email_config')) {
function site_email_config() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/../config/email.php';
    $config = file_exists($path) ? require $path : [];

    return is_array($config) ? $config : [];
}

function site_contact_email() {
    $config = site_email_config();
    return $config['contact_email'] ?? 'service@tresorafricain.com';
}

function site_sender_email() {
    $config = site_email_config();
    return $config['from']['email'] ?? 'no-replaye@tresorafricain.com';
}

function site_sender_name() {
    $config = site_email_config();
    return $config['from']['name'] ?? 'Trésor Africa';
}
}
