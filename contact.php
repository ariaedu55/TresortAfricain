<?php
session_start();

$message_envoye = false;
$erreur = '';

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (empty($csrf) || !isset($_SESSION['contact_csrf']) || !hash_equals($_SESSION['contact_csrf'], $csrf)) {
        $erreur = 'Session expirée. Veuillez réessayer.';
    } else {
        $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $sujet = isset($_POST['sujet']) ? trim($_POST['sujet']) : 'Contact';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';

        if (empty($nom) || empty($email) || empty($message)) {
            $erreur = 'Veuillez remplir tous les champs obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide.';
        } else {
            if (function_exists('mail_send_contact')) {
                $result = mail_send_contact($nom, $email, $sujet, $message);
                if ($result['success']) {
                    $message_envoye = true;
                    unset($_SESSION['contact_csrf']);
                } else {
                    $erreur = $result['error'] ?? 'Erreur lors de l\'envoi. Vérifiez config/email.php.';
                }
            } else {
                $erreur = 'Service email non configuré. Exécutez "composer install" et configurez config/email.php.';
            }
        }
    }
}
if (!isset($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/site_email.php';
require_once __DIR__ . '/includes/asset_version.php';

$email_contact = site_contact_email();

$social_config = [];
if (file_exists(__DIR__ . '/config/social.php')) {
    $social_config = require __DIR__ . '/config/social.php';
}
$contact_facebook = trim((string) ($social_config['facebook'] ?? ''));
$contact_instagram = trim((string) ($social_config['instagram'] ?? ''));
$contact_tiktok = trim((string) ($social_config['tiktok'] ?? ''));
$has_social = ($contact_facebook !== '' || $contact_instagram !== '' || $contact_tiktok !== '');

require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = 'Contact - Trésor Africa';
$seo_description = 'Contactez Trésor Africa : produits naturels, questions et commandes.';
$seo_canonical = $base . '/contact.php';

$sujet_valeur = isset($_POST['sujet']) ? (string) $_POST['sujet'] : 'Contact';
$sujets_predefinis = [
    'Contact' => 'Demande générale',
    'Commande' => 'Suivi de commande',
    'Produit' => 'Question sur un produit',
    'Partenariat' => 'Partenariat / professionnel',
];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/contact.css<?php echo asset_version_query(); ?>">
</head>

<body>
    <?php include('nav_bar.php'); ?>

    <div class="contact-page">
        <header class="contact-hero">
            <div class="contact-hero-inner">
                <p class="contact-eyebrow">Trésor Africa</p>
                <h1>Contactez-nous</h1>
                <p class="contact-lead">Une question, une commande ou une suggestion&nbsp;? Écrivez-nous&nbsp;: nous vous répondons rapidement.</p>
            </div>
        </header>

        <div class="contact-layout">
            <section class="contact-form-panel" aria-labelledby="contact-form-title">
                <div class="contact-form-head">
                    <h2 id="contact-form-title">Envoyer un message</h2>
                    <p>Les champs marqués d’un * sont obligatoires.</p>
                </div>

                <?php if ($message_envoye): ?>
                    <div class="alert alert-success" role="status">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Message envoyé</strong>
                            <span>Nous vous répondrons dans les plus brefs délais.</span>
                        </div>
                    </div>
                    <a href="/contact.php" class="btn-submit btn-submit--ghost">
                        <span class="btn-submit-text">Nouveau message</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Envoi impossible</strong>
                            <span><?php echo htmlspecialchars($erreur); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$message_envoye): ?>
                <form method="POST" action="" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['contact_csrf']); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required autocomplete="name"
                                placeholder="Votre nom"
                                value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" required autocomplete="email"
                                placeholder="votre@email.com"
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="sujet">Sujet</label>
                        <select id="sujet" name="sujet">
                            <?php foreach ($sujets_predefinis as $valeur => $libelle): ?>
                            <option value="<?php echo htmlspecialchars($valeur); ?>"
                                <?php echo $sujet_valeur === $valeur ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($libelle); ?>
                            </option>
                            <?php endforeach; ?>
                            <?php if ($sujet_valeur !== '' && !isset($sujets_predefinis[$sujet_valeur])): ?>
                            <option value="<?php echo htmlspecialchars($sujet_valeur); ?>" selected>
                                <?php echo htmlspecialchars($sujet_valeur); ?>
                            </option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required rows="5"
                            placeholder="Décrivez votre demande…"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span class="btn-submit-text">Envoyer le message</span>
                        <span class="btn-submit-icon" aria-hidden="true"><i class="fa-solid fa-paper-plane"></i></span>
                    </button>
                </form>
                <?php endif; ?>
            </section>

            <aside class="contact-aside" aria-label="Coordonnées">
                <div class="contact-aside-card">
                    <h2>Coordonnées</h2>
                    <p class="contact-aside-text">Préférez écrire directement&nbsp;? Utilisez ces canaux.</p>

                    <a href="mailto:<?php echo htmlspecialchars($email_contact); ?>" class="contact-channel">
                        <span class="contact-channel-icon" aria-hidden="true"><i class="fa-solid fa-envelope"></i></span>
                        <span class="contact-channel-body">
                            <span class="contact-channel-label">E-mail</span>
                            <span class="contact-channel-value"><?php echo htmlspecialchars($email_contact); ?></span>
                        </span>
                        <span class="contact-channel-chevron" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                    </a>

                    <?php if ($has_social): ?>
                    <div class="contact-social-block">
                        <span class="contact-channel-label">Réseaux sociaux</span>
                        <div class="contact-social-row">
                            <?php if ($contact_facebook !== ''): ?>
                            <a href="<?php echo htmlspecialchars($contact_facebook); ?>" class="contact-social-link" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($contact_instagram !== ''): ?>
                            <a href="<?php echo htmlspecialchars($contact_instagram); ?>" class="contact-social-link" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($contact_tiktok !== ''): ?>
                            <a href="<?php echo htmlspecialchars($contact_tiktok); ?>" class="contact-social-link" target="_blank" rel="noopener noreferrer" title="TikTok" aria-label="TikTok">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <ul class="contact-promises">
                        <li><i class="fa-solid fa-clock" aria-hidden="true"></i> Réponse sous 24–48&nbsp;h</li>
                        <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Données traitées avec soin</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>

</html>
