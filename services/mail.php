<?php
/**
 * Service d'envoi d'emails via PHPMailer
 * Nécessite : composer require phpmailer/phpmailer
 * Configuration : config/email.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/site_email.php';
require_once __DIR__ . '/../includes/text_encoding.php';

/**
 * Charge la configuration email
 * @return array|null Configuration ou null si absente
 */
function mail_get_config() {
    $config = site_email_config();
    return !empty($config) ? $config : null;
}

function mail_get_contact_email() {
    return site_contact_email();
}

function mail_get_from_email() {
    return site_sender_email();
}

function mail_get_from_name() {
    return site_sender_name();
}

/**
 * Normalise une chaîne en UTF-8 valide
 */
function mail_normalize_utf8($text) {
    return normalize_utf8($text);
}

/**
 * Enveloppe HTML UTF-8 pour compatibilité clients mail
 */
function mail_wrap_html_body($body, $subject = '') {
    $body = mail_normalize_utf8($body);
    $title = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html>'
        . '<html lang="fr"><head>'
        . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
        . '<meta charset="UTF-8">'
        . '<title>' . $title . '</title>'
        . '</head><body style="margin:0;padding:0;font-family:Arial,sans-serif;">'
        . $body
        . '</body></html>';
}

/**
 * Version texte brut d'un contenu HTML
 */
function mail_to_plain_text($html) {
    $text = html_entity_decode(
        strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $html)),
        ENT_QUOTES,
        'UTF-8'
    );

    return preg_replace("/\n{3,}/", "\n\n", trim($text));
}

/**
 * Prépare sujet et corps avant envoi
 */
function mail_prepare_message($subject, $body, $isHtml = true) {
    $subject = mail_normalize_utf8($subject);

    if ($isHtml) {
        $body = mail_wrap_html_body($body, $subject);
        $altBody = mail_to_plain_text($body);
    } else {
        $body = mail_normalize_utf8($body);
        $altBody = $body;
    }

    return [
        'subject' => $subject,
        'body' => $body,
        'alt_body' => $altBody,
    ];
}

/**
 * Crée et configure une instance PHPMailer
 * @return PHPMailer|null Instance configurée ou null en cas d'erreur
 */
function mail_create_instance() {
    $config = mail_get_config();
    if (!$config) {
        return null;
    }

    $mail = new PHPMailer(true);
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->Encoding = 'quoted-printable';
    $mail->setLanguage('fr', __DIR__ . '/../vendor/phpmailer/phpmailer/language/');

    $from = $config['from'] ?? [];
    $mail->setFrom(
        $from['email'] ?? mail_get_from_email(),
        $from['name'] ?? mail_get_from_name()
    );

    if (($config['debug'] ?? false) === true) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }

    switch ($config['method'] ?? 'mail') {
        case 'smtp':
            $mail->isSMTP();
            $smtp = $config['smtp'] ?? [];
            $mail->Host       = $smtp['host'] ?? 'localhost';
            $mail->Port       = (int) ($smtp['port'] ?? 587);
            $mail->Timeout     = (int) ($smtp['timeout'] ?? 30);
            $mail->SMTPAuth    = !empty($smtp['username']);
            if ($mail->SMTPAuth) {
                $mail->Username   = $smtp['username'] ?? '';
                $mail->Password   = $smtp['password'] ?? '';
            }
            $enc = $smtp['encryption'] ?? 'tls';
            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            // Hébergement partagé : certificat émis pour *.web-hosting.com au lieu de mail.domaine.com
            if (isset($smtp['verify_ssl']) && $smtp['verify_ssl'] === false) {
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }
            break;
        case 'sendmail':
            $mail->isSendmail();
            break;
        default:
            $mail->isMail();
            break;
    }

    return $mail;
}

/**
 * Envoie un email simple
 * @param string $to Destinataire
 * @param string $subject Sujet
 * @param string $body Corps du message (HTML ou texte)
 * @param bool $isHtml true si le corps est en HTML
 * @return array ['success' => bool, 'error' => string|null]
 */
function mail_send($to, $subject, $body, $isHtml = true) {
    $config = mail_get_config();
    if (!$config) {
        return ['success' => false, 'error' => 'Configuration email manquante (config/email.php)'];
    }

    if (($config['method'] ?? 'mail') === 'log') {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $plain_body = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        $entry = str_repeat('-', 60) . "\n"
            . date('Y-m-d H:i:s') . " | To: {$to} | Subject: {$subject}\n"
            . $plain_body . "\n";
        file_put_contents($log_dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
        return ['success' => true, 'error' => null, 'logged' => true];
    }

    $mail = mail_create_instance();
    if (!$mail) {
        return ['success' => false, 'error' => 'Configuration email invalide'];
    }

    try {
        $prepared = mail_prepare_message($subject, $body, $isHtml);
        $mail->addAddress($to);
        $mail->Subject = $prepared['subject'];
        $mail->isHTML($isHtml);
        $mail->Body = $prepared['body'];
        $mail->AltBody = $prepared['alt_body'];
        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

/**
 * Envoie l'email du formulaire de contact
 * @param string $nom Nom de l'expéditeur
 * @param string $email Email de l'expéditeur
 * @param string $sujet Sujet du message
 * @param string $message Corps du message
 * @return array ['success' => bool, 'error' => string|null]
 */
function mail_send_contact($nom, $email, $sujet, $message) {
    $contact = mail_get_contact_email();

    $nom = mail_normalize_utf8($nom);
    $sujet = mail_normalize_utf8($sujet);
    $message = mail_normalize_utf8($message);

    $body = "<p><strong>De :</strong> " . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . " &lt;" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "&gt;</p>";
    $body .= "<p><strong>Sujet :</strong> " . htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') . "</p>";
    $body .= "<hr><p>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>";

    $result = mail_send_with_reply_to($contact, "[Contact] " . $sujet, $body, $email, $nom);
    if ($result['success']) {
        $reply = "Merci pour votre message. Nous vous répondrons rapidement.";
        mail_send($email, "Confirmation - Trésor Africa", "<p>" . htmlspecialchars($reply) . "</p>", true);
    }
    return $result;
}

/**
 * Envoie un email avec adresse Reply-To optionnelle
 */
function mail_send_with_reply_to($to, $subject, $body, $reply_to_email = '', $reply_to_name = '', $isHtml = true) {
    $config = mail_get_config();
    if (!$config) {
        return ['success' => false, 'error' => 'Configuration email manquante (config/email.php)'];
    }

    if (($config['method'] ?? 'mail') === 'log') {
        $log_dir = __DIR__ . '/../logs';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        $plain_body = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        $entry = str_repeat('-', 60) . "\n"
            . date('Y-m-d H:i:s') . " | To: {$to} | Subject: {$subject}\n"
            . ($reply_to_email ? "Reply-To: {$reply_to_email}\n" : '')
            . $plain_body . "\n";
        file_put_contents($log_dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
        return ['success' => true, 'error' => null, 'logged' => true];
    }

    $mail = mail_create_instance();
    if (!$mail) {
        return ['success' => false, 'error' => 'Configuration email invalide'];
    }

    try {
        $prepared = mail_prepare_message($subject, $body, $isHtml);
        $mail->addAddress($to);
        if (!empty($reply_to_email) && filter_var($reply_to_email, FILTER_VALIDATE_EMAIL)) {
            $reply_name = mail_normalize_utf8($reply_to_name ?: $reply_to_email);
            $mail->addReplyTo($reply_to_email, $reply_name);
        }
        $mail->Subject = $prepared['subject'];
        $mail->isHTML($isHtml);
        $mail->Body = $prepared['body'];
        $mail->AltBody = $prepared['alt_body'];
        $mail->send();
        return ['success' => true, 'error' => null];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo ?: $e->getMessage()];
    }
}

/**
 * Envoie l'email de réinitialisation de mot de passe
 * @param string $email Destinataire
 * @param string $reset_link Lien de réinitialisation
 * @param string $type 'user' ou 'admin' (pour adapter le texte)
 * @return array ['success' => bool, 'error' => string|null]
 */
function mail_send_reset_link($email, $reset_link, $type = 'user') {
    $is_admin = ($type === 'admin');
    $sujet = 'Réinitialisation de votre mot de passe - Trésor Africa';

    $body = '<div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;">';
    $body .= '<h2 style="color: #c26638;">Réinitialisation du mot de passe</h2>';
    $body .= '<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>';
    $body .= '<p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>';
    $body .= '<p style="margin: 25px 0;"><a href="' . htmlspecialchars($reset_link) . '" style="display: inline-block; padding: 12px 24px; background: #c26638; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600;">Réinitialiser mon mot de passe</a></p>';
    $body .= '<p style="font-size: 13px; color: #666;">Ou copiez ce lien dans votre navigateur :<br><span style="word-break: break-all;">' . htmlspecialchars($reset_link) . '</span></p>';
    $body .= '<p style="font-size: 12px; color: #999; margin-top: 30px;">Ce lien expire dans 2 heures. Si vous n\'avez pas fait cette demande, ignorez cet email.</p>';
    $body .= '<hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">';
    $body .= '<p style="font-size: 12px; color: #999;">Trésor Africa - Produits naturels</p>';
    $body .= '</div>';

    return mail_send($email, $sujet, $body, true);
}
