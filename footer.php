<?php
require_once __DIR__ . '/includes/site_email.php';
require_once __DIR__ . '/includes/brand.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$footer_contact_email = site_contact_email();
$footer_brand_name = brand_name();

if (!isset($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$social_config = [];
if (file_exists(__DIR__ . '/config/social.php')) {
    $social_config = require __DIR__ . '/config/social.php';
}
$footer_facebook = trim((string) ($social_config['facebook'] ?? ''));
$footer_instagram = trim((string) ($social_config['instagram'] ?? ''));
$footer_tiktok = trim((string) ($social_config['tiktok'] ?? ''));

require_once __DIR__ . '/includes/asset_version.php';
?>
<link rel="stylesheet" href="/css/footer.css<?php echo asset_version_query(); ?>">
<footer class="footer">
    <div class="footer_main">
        <div class="footer_container">
            <div class="footer_col footer_col--brand">
                <a href="/index.php" class="footer_logo">
                    <img src="<?php echo htmlspecialchars(brand_logo()); ?>" alt="<?php echo htmlspecialchars($footer_brand_name); ?>" class="footer_logo_img"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                    <span class="footer_logo_fallback"><i class="fas fa-leaf"></i> <?php echo htmlspecialchars($footer_brand_name); ?></span>
                </a>
                <span class="footer_tagline">Produits naturels</span>
                <h4 class="footer_about_title">À propos</h4>
                <p class="footer_p">
                    <?php echo htmlspecialchars($footer_brand_name); ?> à votre service. Des produits naturels issus de notre propre production, pour votre bien-être au quotidien.
                </p>
            </div>

            <div class="footer_col">
                <h3 class="footer_item_titl">Catalogue</h3>
                <ul class="footer_list">
                    <li class="footer_list_item"><a href="/produits.php">Tous les produits</a></li>
                    <li class="footer_list_item"><a href="/nouveautes.php">Nouveautés</a></li>
                    <li class="footer_list_item"><a href="/promo.php">Promotions</a></li>
                    <li class="footer_list_item"><a href="/contact.php">Contact</a></li>
                </ul>
            </div>

            <div class="footer_col">
                <h3 class="footer_item_titl">Liens rapides</h3>
                <ul class="footer_list">
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])): ?>
                        <li class="footer_list_item"><a href="/user/mon-compte.php">Mon compte</a></li>
                        <li class="footer_list_item"><a href="/user/deconnexion.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li class="footer_list_item"><a href="/user/connexion.php">Connexion</a></li>
                        <li class="footer_list_item"><a href="/user/inscription.php">Inscription</a></li>
                    <?php endif; ?>
                    <li class="footer_list_item"><a href="/panier.php">Panier</a></li>
                    <li class="footer_list_item"><a href="/produits.php">Boutique</a></li>
                </ul>
            </div>

            <div class="footer_col footer_col--contact">
                <h3 class="footer_item_titl">Contact</h3>
                <p class="footer_contact_line">
                    <strong>Email :</strong>
                    <a href="mailto:<?php echo htmlspecialchars($footer_contact_email); ?>"><?php echo htmlspecialchars($footer_contact_email); ?></a>
                </p>
                <p class="footer_contact_line">
                    <strong>Appelez :</strong>
                    <a href="/contact.php">Contactez-nous</a>
                </p>
                <span class="footer_accent_bar" aria-hidden="true"></span>
            </div>

            <div class="footer_col footer_col--social">
                <form class="footer_newsletter" method="POST" action="/contact.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['contact_csrf']); ?>">
                    <input type="hidden" name="nom" value="Newsletter">
                    <input type="hidden" name="sujet" value="Inscription newsletter">
                    <input type="hidden" name="message" value="Demande d'inscription à la newsletter depuis le pied de page.">
                    <input type="email" name="email" class="footer_newsletter_input" placeholder="Votre email" required
                        aria-label="Votre adresse email pour la newsletter">
                    <button type="submit" class="footer_newsletter_btn" aria-label="S'inscrire à la newsletter">
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="footer_social_row">
                    <?php if ($footer_facebook !== ''): ?>
                        <a href="<?php echo htmlspecialchars($footer_facebook); ?>" class="footer_social_btn footer_social_btn--primary" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook">
                            <i class="fab fa-facebook-f" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($footer_tiktok !== ''): ?>
                        <a href="<?php echo htmlspecialchars($footer_tiktok); ?>" class="footer_social_btn footer_social_btn--outline" target="_blank" rel="noopener noreferrer" title="TikTok" aria-label="TikTok">
                            <i class="fab fa-tiktok" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($footer_instagram !== ''): ?>
                        <a href="<?php echo htmlspecialchars($footer_instagram); ?>" class="footer_social_btn footer_social_btn--outline" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">
                            <i class="fab fa-instagram" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <a href="/contact.php" class="footer_social_btn footer_social_btn--outline" title="Contact" aria-label="Contact">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    </a>
                </div>

                <h4 class="footer_follow_title">Suivez-nous</h4>
            </div>
        </div>
    </div>

    <div class="footer_bottom">
        <div class="footer_bottom_container">
            <div class="footer_bottom_links">
                <a href="/politique-confidentialite.php">Politique de confidentialité</a>
                <span class="footer_bottom_sep">|</span>
                <a href="/conditions-utilisation.php">Conditions d'utilisation</a>
                <span class="footer_bottom_sep">|</span>
                <a href="/contact.php">Contact</a>
            </div>
            <p class="footer_copy">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($footer_brand_name); ?>. Tous droits réservés.
            </p>
        </div>
    </div>
</footer>
<?php include __DIR__ . '/includes/social_floating.php'; ?>
<script>
window.ProductCardConfig = {
    isLoggedIn: <?php echo (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) ? 'true' : 'false'; ?>,
    loginUrl: '/user/connexion.php',
    brandName: <?php echo json_encode($footer_brand_name ?? 'Trésor Africa', JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="/js/product-card-actions.js<?php echo asset_version_query(); ?>"></script>
<script src="/js/nav-search.js<?php echo asset_version_query(); ?>"></script>
