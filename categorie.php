<?php
session_start();

require_once __DIR__ . '/models/model_categories.php';
require_once __DIR__ . '/models/model_produits.php';
require_once __DIR__ . '/includes/image_optimizer.php';
require_once __DIR__ . '/includes/category_images.php';
require_once __DIR__ . '/includes/product_card.php';
require_once __DIR__ . '/includes/asset_version.php';

$favori_ids = product_card_get_favori_ids();
$categorie_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tri = isset($_GET['tri']) && in_array($_GET['tri'], ['date', 'nom', 'prix_asc', 'prix_desc'], true)
    ? $_GET['tri']
    : 'date';

unset($categorie);
$categorie = null;

if ($categorie_id > 0) {
    $categorie = get_categorie_by_id($categorie_id);
}

if (!$categorie || !is_array($categorie) || empty($categorie['nom'])) {
    header('Location: index.php');
    exit;
}

$categorie_nom = (string) ($categorie['nom'] ?? 'Catégorie');
$categorie_desc = trim(strip_tags((string) ($categorie['description'] ?? '')));
$categorie_image = categorie_resolve_image($categorie);

$produits = get_produits_by_categorie($categorie_id);
if ($produits === false) {
    $produits = [];
}

if (!empty($produits)) {
    usort($produits, function ($a, $b) use ($tri) {
        $prix_a = (!empty($a['prix_promotion']) && $a['prix_promotion'] < $a['prix'])
            ? (float) $a['prix_promotion']
            : (float) ($a['prix'] ?? 0);
        $prix_b = (!empty($b['prix_promotion']) && $b['prix_promotion'] < $b['prix'])
            ? (float) $b['prix_promotion']
            : (float) ($b['prix'] ?? 0);

        if ($tri === 'nom') {
            return strcasecmp((string) ($a['nom'] ?? ''), (string) ($b['nom'] ?? ''));
        }
        if ($tri === 'prix_asc') {
            return $prix_a <=> $prix_b;
        }
        if ($tri === 'prix_desc') {
            return $prix_b <=> $prix_a;
        }
        $da = strtotime((string) ($a['date_creation'] ?? '')) ?: 0;
        $db_ = strtotime((string) ($b['date_creation'] ?? '')) ?: 0;
        return $db_ <=> $da;
    });
}

$total_produits = count($produits);

$autres_categories = [];
$all_cats = get_all_categories();
if (is_array($all_cats)) {
    foreach ($all_cats as $cat) {
        $cid = (int) ($cat['id'] ?? 0);
        if ($cid <= 0) {
            continue;
        }
        $autres_categories[] = $cat;
        if (count($autres_categories) >= 12) {
            break;
        }
    }
}

if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = $categorie_nom . ' - Trésor Africa';
$desc_cat = $categorie_desc !== ''
    ? $categorie_desc
    : ('Produits naturels ' . $categorie_nom . ' — Trésor Africa.');
$seo_description = mb_substr($desc_cat, 0, 160);
$seo_canonical = $base . '/categorie.php?id=' . (int) $categorie_id;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/includes/pwa_meta.php'; ?>
    <?php include __DIR__ . '/includes/seo_meta.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/category-page.css<?php echo asset_version_query(); ?>">
</head>

<body>
    <?php include('nav_bar.php'); ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div class="category-flash category-flash--ok" role="status">
        <i class="fas fa-check-circle" aria-hidden="true"></i> Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="category-flash category-flash--err" role="alert">
        <i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <?php endif; ?>

    <main class="category-page">
        <header class="category-hero<?php echo $categorie_image !== '' ? ' category-hero--has-media' : ''; ?>">
            <div class="category-hero-content">
                <a href="/produits.php" class="category-back">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    <span>Catalogue</span>
                </a>
                <span class="category-hero-surtitre">Sélection Trésor Africa</span>
                <h1 class="category-hero-titre"><?php echo htmlspecialchars($categorie_nom); ?></h1>
            </div>
            <?php if ($categorie_image !== ''): ?>
            <div class="category-hero-media" aria-hidden="true">
                <?php render_upload_img($categorie_image, '', ['class' => 'category-hero-img']); ?>
            </div>
            <?php endif; ?>
        </header>

        <?php if (!empty($produits)): ?>
        <div class="category-toolbar" id="category-products">
            <p class="category-toolbar-count">
                <strong><?php echo (int) $total_produits; ?></strong>
                produit<?php echo $total_produits > 1 ? 's' : ''; ?>
            </p>
            <form class="category-sort" method="get" action="/categorie.php">
                <input type="hidden" name="id" value="<?php echo (int) $categorie_id; ?>">
                <label for="tri-categorie">Trier</label>
                <select id="tri-categorie" name="tri" onchange="this.form.submit()">
                    <option value="date" <?php echo $tri === 'date' ? 'selected' : ''; ?>>Plus récents</option>
                    <option value="nom" <?php echo $tri === 'nom' ? 'selected' : ''; ?>>Nom A–Z</option>
                    <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                </select>
            </form>
        </div>

        <section class="category-products" aria-label="Produits de la catégorie">
            <article data-aos="fade-up" data-aos-duration="800" data-aos-once="true"
                class="articles carousel11 produits-grid-4">
                <?php foreach ($produits as $produit): ?>
                <?php
                    $has_promo = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
                    $prix_principal = number_format((float) $produit['prix'], 0, ',', ' ');
                    $prix_promo_value = $has_promo ? number_format((float) $produit['prix_promotion'], 0, ',', ' ') : '';
                    $pourcentage_reduction = $has_promo
                        ? (int) round((($produit['prix'] - $produit['prix_promotion']) / $produit['prix']) * 100)
                        : 0;
                ?>
                <div class="carousel">
                    <?php render_product_card_image_block($produit, [
                        'is_favori' => in_array((int) $produit['id'], $favori_ids, true),
                    ]); ?>
                    <a href="produit.php?id=<?php echo (int) $produit['id']; ?>" class="product-card-link">
                        <div class="produit-content">
                            <p id="nom"><?php echo htmlspecialchars($produit['nom']); ?></p>
                            <p class="prix">
                                <?php if ($has_promo): ?>
                                <span class="span2"><?php echo $prix_principal; ?> FCFA</span>
                                <span class="prix-promo"><?php echo $prix_promo_value; ?> FCFA</span>
                                <span class="span3">-<?php echo $pourcentage_reduction; ?>%</span>
                                <?php else: ?>
                                <?php echo $prix_principal; ?><span class="span1"> FCFA</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </a>
                    <form method="POST" action="/add-to-panier.php" class="add-to-cart-form">
                        <input type="hidden" name="produit_id" value="<?php echo (int) $produit['id']; ?>">
                        <input type="hidden" name="quantite" value="1">
                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/categorie.php'); ?>">
                        <button type="submit" class="btn-add-cart">
                            <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </article>
        </section>
        <?php else: ?>
        <div class="category-empty">
            <i class="fas fa-box-open" aria-hidden="true"></i>
            <p>Aucun produit publié dans cette catégorie pour le moment.</p>
        </div>
        <?php endif; ?>

        <?php if (!empty($autres_categories)): ?>
        <section class="category-others" aria-label="Autres catégories">
            <h2>Explorer d’autres catégories</h2>
            <div class="category-others-scroll">
                <?php foreach ($autres_categories as $cat): ?>
                <?php
                    $cid = (int) ($cat['id'] ?? 0);
                    $cnom = (string) ($cat['nom'] ?? 'Catégorie');
                    $is_current = $cid === $categorie_id;
                ?>
                <a href="/categorie.php?id=<?php echo $cid; ?>"
                    class="category-other-pill<?php echo $is_current ? ' is-current' : ''; ?>"
                    <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                    <?php echo htmlspecialchars($cnom); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <?php include('footer.php'); ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, duration: 700 });
    </script>
</body>

</html>
