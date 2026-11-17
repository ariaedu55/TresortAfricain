<?php
/**
 * Boutons favori et partage sur les cartes produit
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/site_url.php';
require_once __DIR__ . '/image_optimizer.php';

if (!function_exists('product_card_get_favori_ids')) {
    /**
     * IDs des produits favoris de l'utilisateur connecté
     * @return array
     */
    function product_card_get_favori_ids() {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        if (!isset($_SESSION['user_id'])) {
            return $cache;
        }

        if (!function_exists('get_favori_produit_ids_by_user')) {
            require_once __DIR__ . '/../models/model_favoris.php';
        }

        $cache = get_favori_produit_ids_by_user((int) $_SESSION['user_id']);
        return $cache;
    }
}

if (!function_exists('product_card_share_url')) {
    /**
     * URL publique d'un produit
     * @param int $produit_id
     * @return string
     */
    function product_card_share_url($produit_id) {
        return get_site_base_url() . '/produit.php?id=' . (int) $produit_id;
    }
}

if (!function_exists('render_product_card_actions')) {
    /**
     * Affiche les boutons favori et partage
     * @param int $produit_id
     * @param string $produit_nom
     * @param array $options
     * @return void
     */
    function render_product_card_actions($produit_id, $produit_nom, $options = []) {
        $produit_id = (int) $produit_id;
        if ($produit_id <= 0) {
            return;
        }

        $produit_nom = trim((string) $produit_nom);
        if ($produit_nom === '') {
            $produit_nom = 'Produit';
        }

        $is_favori = !empty($options['is_favori']);
        $is_logged_in = isset($_SESSION['user_id']);
        $return_url = isset($options['return_url']) && $options['return_url'] !== ''
            ? (string) $options['return_url']
            : ($_SERVER['REQUEST_URI'] ?? '/index.php');
        $share_url = isset($options['share_url']) && $options['share_url'] !== ''
            ? (string) $options['share_url']
            : product_card_share_url($produit_id);
        $extra_class = isset($options['extra_class']) ? (string) $options['extra_class'] : '';

        $heart_icon = $is_favori ? 'fa-solid' : 'fa-regular';
        $favori_label = $is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris';
        ?>
        <div class="product-card-actions<?php echo $extra_class !== '' ? ' ' . htmlspecialchars($extra_class) : ''; ?>"
            data-produit-id="<?php echo $produit_id; ?>">
            <button type="button"
                class="btn-product-action btn-product-favori<?php echo $is_favori ? ' is-favori' : ''; ?><?php echo !$is_logged_in ? ' needs-login' : ''; ?>"
                title="<?php echo htmlspecialchars($favori_label); ?>"
                aria-label="<?php echo htmlspecialchars($favori_label); ?>"
                aria-pressed="<?php echo $is_favori ? 'true' : 'false'; ?>"
                data-produit-id="<?php echo $produit_id; ?>"
                data-return-url="<?php echo htmlspecialchars($return_url); ?>">
                <i class="<?php echo $heart_icon; ?> fa-heart"></i>
            </button>
            <button type="button" class="btn-product-action btn-product-share"
                title="Partager ce produit" aria-label="Partager ce produit"
                data-share-url="<?php echo htmlspecialchars($share_url); ?>"
                data-share-title="<?php echo htmlspecialchars($produit_nom); ?>">
                <i class="fa-solid fa-share-nodes"></i>
            </button>
        </div>
        <?php
    }
}

if (!function_exists('product_card_actions_html')) {
    /**
     * Version HTML pour chargement dynamique (JS)
     * @param int $produit_id
     * @param string $produit_nom
     * @param bool $is_favori
     * @param string $return_url
     * @param string|null $share_url
     * @return string
     */
    function product_card_actions_html($produit_id, $produit_nom, $is_favori = false, $return_url = '', $share_url = null, $is_logged_in = null) {
        $produit_id = (int) $produit_id;
        $produit_nom = htmlspecialchars(trim((string) $produit_nom) ?: 'Produit', ENT_QUOTES, 'UTF-8');
        $return_url = htmlspecialchars($return_url !== '' ? $return_url : ($_SERVER['REQUEST_URI'] ?? '/index.php'), ENT_QUOTES, 'UTF-8');
        $share_url = htmlspecialchars($share_url ?: product_card_share_url($produit_id), ENT_QUOTES, 'UTF-8');
        $heart_icon = $is_favori ? 'fa-solid' : 'fa-regular';
        $favori_class = $is_favori ? ' is-favori' : '';
        $favori_label = $is_favori ? 'Retirer des favoris' : 'Ajouter aux favoris';
        if ($is_logged_in === null) {
            $is_logged_in = isset($_SESSION['user_id']);
        }
        $login_class = $is_logged_in ? '' : ' needs-login';

        return '<div class="product-card-actions" data-produit-id="' . $produit_id . '">'
            . '<button type="button" class="btn-product-action btn-product-favori' . $favori_class . $login_class . '"'
            . ' title="' . $favori_label . '" aria-label="' . $favori_label . '"'
            . ' aria-pressed="' . ($is_favori ? 'true' : 'false') . '"'
            . ' data-produit-id="' . $produit_id . '" data-return-url="' . $return_url . '">'
            . '<i class="' . $heart_icon . ' fa-heart"></i></button>'
            . '<button type="button" class="btn-product-action btn-product-share" title="Partager ce produit" aria-label="Partager ce produit"'
            . ' data-share-url="' . $share_url . '" data-share-title="' . $produit_nom . '">'
            . '<i class="fa-solid fa-share-nodes"></i></button></div>';
    }
}

if (!function_exists('render_product_card_image_block')) {
    /**
     * Bloc image carte produit avec favori / partage
     * @param array $produit
     * @param array $options
     * @return void
     */
    function render_product_card_image_block($produit, $options = []) {
        $produit_id = (int) ($produit['id'] ?? 0);
        $produit_nom = $produit['nom'] ?? 'Produit';
        $is_favori = !empty($options['is_favori']);
        $show_nouveau = !empty($options['show_nouveau_badge']);
        ?>
        <div class="image-wrapper">
            <?php if ($show_nouveau): ?>
            <span class="produit-card-badge produit-badge-nouveau">Nouveau</span>
            <?php endif; ?>
            <?php render_product_card_actions($produit_id, $produit_nom, [
                'is_favori' => $is_favori,
                'return_url' => $options['return_url'] ?? null,
            ]); ?>
            <a href="produit.php?id=<?php echo $produit_id; ?>" class="product-card-image-link">
                <?php render_upload_img($produit['image_principale'] ?? '', $produit_nom); ?>
            </a>
        </div>
        <?php
    }
}

?>
