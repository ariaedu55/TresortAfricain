<?php
session_start();

require_once __DIR__ . '/models/model_produits.php';
require_once __DIR__ . '/includes/image_optimizer.php';
require_once __DIR__ . '/includes/product_card.php';

$produits = get_produits_nouveautes(16);
$favori_ids = product_card_get_favori_ids();

if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = 'Nouveautés produits naturels - Trésor Africa';
$seo_description = 'Découvrez les 16 derniers produits naturels ajoutés chez Trésor Africa : noix, huiles, céréales et bien plus.';
$seo_canonical = $base . '/nouveautes.php';
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
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css">
    <style>
        .page-header {
            background: var(--couleur-dominante);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .produits-container-wrapper {
            max-width: 1320px;
            width: 100%;
            margin: 0 auto;
            padding: 0 clamp(12px, 3vw, 20px) 80px;
            box-sizing: border-box;
            overflow-x: hidden;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.4;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: var(--couleur-dominante);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .empty-state a:hover {
            background: rgba(194, 102, 56, 0.9);
        }
    </style>
</head>

<body>
    <?php include('nav_bar.php'); ?>

    <div class="page-header">
        <h1><i class="fas fa-gift"></i> Nouveautés</h1>
        <p>Les 16 derniers produits ajoutés à notre catalogue</p>
    </div>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(145, 138, 68, 0.15); border-left: 4px solid var(--turquoise); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(194, 102, 56, 0.15); border-left: 4px solid var(--couleur-dominante); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <?php endif; ?>
    <div class="produits-container-wrapper">
        <section class="section00 produit_vedete-nouveautes">
            <section class="produit_vedetes">
                <?php if (empty($produits)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>Aucun produit pour le moment.</p>
                        <a href="index.php"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
                    </div>
                <?php else: ?>
                    <article data-aos="fade-up" class="articles carousel11 produits-grid-4">
                        <?php foreach ($produits as $produit): ?>
                            <?php
                            $has_promo = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
                            $prix_affichage = $has_promo ? $produit['prix_promotion'] : $produit['prix'];
                            ?>
                            <div class="carousel">
                                <?php render_product_card_image_block($produit, [
                                    'show_nouveau_badge' => true,
                                    'is_favori' => in_array((int) $produit['id'], $favori_ids, true),
                                ]); ?>
                                <a href="produit.php?id=<?php echo $produit['id']; ?>" class="product-card-link">
                                    <div class="produit-content">
                                        <p id="nom"><?php echo htmlspecialchars($produit['nom']); ?></p>
                                        <p class="prix">
                                            <?php if ($has_promo): ?>
                                                <span class="span2"><?php echo number_format($produit['prix'], 0, ',', ' '); ?>
                                                    FCFA</span>
                                                <span class="prix-promo"><?php echo number_format($prix_affichage, 0, ',', ' '); ?>
                                                    FCFA</span>
                                            <?php else: ?>
                                                <?php echo number_format($prix_affichage, 0, ',', ' '); ?><span class="span1">
                                                    FCFA</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </a>
                                <form method="POST" action="/add-to-panier.php" class="add-to-cart-form">
                                    <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                    <input type="hidden" name="quantite" value="1">
                                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/nouveautes.php'); ?>">
                                    <button type="submit" class="btn-add-cart">
                                        <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </article>
                <?php endif; ?>
            </section>
        </section>
    </div>

    <?php include('footer.php'); ?>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>AOS.init();</script>
</body>

</html>