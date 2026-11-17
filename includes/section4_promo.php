<?php
/**
 * Helpers d'affichage pour les bannières promo section4
 * Programmation procédurale uniquement
 */

if (!function_exists('section4_promo_image_url')) {
    /**
     * Retourne l'URL publique d'une image section4
     * @param string|null $filename
     * @param string $fallback
     * @return string
     */
    function section4_promo_image_url($filename, $fallback = '/image/market.png') {
        if (empty($filename)) {
            return $fallback;
        }

        $upload_rel = '/upload/section4/' . $filename;
        $upload_abs = __DIR__ . '/../upload/section4/' . $filename;
        if (file_exists($upload_abs)) {
            return $upload_rel;
        }

        $legacy_abs = __DIR__ . '/../image/' . $filename;
        if (file_exists($legacy_abs)) {
            return '/image/' . $filename;
        }

        return $fallback;
    }
}

if (!function_exists('section4_promo_normalize_link')) {
    /**
     * Normalise un lien interne ou externe
     * @param string $lien
     * @return string
     */
    function section4_promo_normalize_link($lien) {
        $lien = trim((string) $lien);
        if ($lien === '') {
            return 'produits.php';
        }
        if (preg_match('#^https?://#i', $lien)) {
            return $lien;
        }
        return ltrim($lien, '/');
    }
}

if (!function_exists('section4_promo_sanitize_color')) {
    /**
     * Valide une couleur hexadécimale
     * @param string $color
     * @param string $default
     * @return string
     */
    function section4_promo_sanitize_color($color, $default) {
        $color = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            return $color;
        }
        return $default;
    }
}

if (!function_exists('section4_promo_render_banner')) {
    /**
     * Affiche une bannière promo
     * @param array $banner
     * @return void
     */
    function section4_promo_render_banner($banner) {
        $surtitre = trim($banner['surtitre'] ?? '');
        $titre = trim($banner['titre'] ?? '');
        $bouton = trim($banner['bouton'] ?? '');
        $lien = section4_promo_normalize_link($banner['lien'] ?? '');
        $bg = section4_promo_sanitize_color($banner['bg_color'] ?? '', '#faf7f2');
        $style = ($banner['style_bouton'] ?? 'solid') === 'outline' ? 'outline' : 'solid';
        $image = section4_promo_image_url($banner['image'] ?? '', '/image/market.png');

        if ($surtitre === '' && $titre === '' && $bouton === '') {
            return;
        }
        ?>
        <article class="promo-banner" style="background-color: <?php echo htmlspecialchars($bg); ?>;">
            <div class="promo-banner__content">
                <?php if ($surtitre !== ''): ?>
                <span class="promo-banner__surtitre"><?php echo htmlspecialchars($surtitre); ?></span>
                <?php endif; ?>
                <?php if ($titre !== ''): ?>
                <h2 class="promo-banner__titre"><?php echo htmlspecialchars($titre); ?></h2>
                <?php endif; ?>
                <?php if ($bouton !== ''): ?>
                <a href="<?php echo htmlspecialchars($lien); ?>" class="promo-banner__btn promo-banner__btn--<?php echo $style; ?>">
                    <?php echo htmlspecialchars($bouton); ?>
                </a>
                <?php endif; ?>
            </div>
            <div class="promo-banner__media">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($titre !== '' ? $titre : $surtitre); ?>" loading="lazy">
            </div>
        </article>
        <?php
    }
}

?>
