<?php
/**
 * Boutons réseaux sociaux en position fixe (bas à droite)
 * Instagram, TikTok, Facebook
 */

$social_config = [];
if (file_exists(__DIR__ . '/../config/social.php')) {
    $social_config = require __DIR__ . '/../config/social.php';
}

$instagram = $social_config['instagram'] ?? '';
$tiktok = $social_config['tiktok'] ?? '';
$facebook = $social_config['facebook'] ?? '';
?>
<?php if (!function_exists('get_asset_version')) { require_once __DIR__ . '/asset_version.php'; } ?>
<link rel="stylesheet" href="/css/social-floating.css<?php echo asset_version_query(); ?>">
<div class="social-floating" id="socialFloating" aria-label="Réseaux sociaux">
    <?php if (!empty($instagram)): ?>
    <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-instagram" title="Suivez-nous sur Instagram">
        <i class="fab fa-instagram"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($tiktok)): ?>
    <a href="<?php echo htmlspecialchars($tiktok); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-tiktok" title="Suivez-nous sur TikTok">
        <i class="fab fa-tiktok"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($facebook)): ?>
    <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" rel="noopener noreferrer" class="social-floating-btn social-facebook" title="Suivez-nous sur Facebook">
        <i class="fab fa-facebook-f"></i>
    </a>
    <?php endif; ?>
</div>
