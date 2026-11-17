<?php
/**
 * Configuration email - PHPMailer / SMTP
 * Copiez ce fichier en config/email.php et modifiez les valeurs
 * NE JAMAIS committer config/email.php (ajoutez-le à .gitignore)
 *
 * SSL/TLS (recommandé) :
 *   Host: tresorafricain.com
 *   Port: 465
 *   Utilisateur SMTP: no-replaye@tresorafricain.com
 *   Authentification: oui
 */

return [
    // 'log' = développement local (fichier logs/mail.log), 'smtp' = production
    'method' => 'smtp',
    'site_url' => 'https://www.tresorafricain.com',

    'smtp' => [
        'host' => 'tresorafricain.com',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'no-replaye@tresorafricain.com',
        'password' => 'VOTRE_MOT_DE_PASSE_BOITE_MAIL',
        'timeout' => 30,
        'verify_ssl' => false,
    ],

    'from' => [
        'email' => 'no-replaye@tresorafricain.com',
        'name' => 'Trésor Africa',
    ],

    // Boîte qui reçoit les messages du formulaire de contact
    'contact_email' => 'service@tresorafricain.com',

    'debug' => false,
];
