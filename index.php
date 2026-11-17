<?php
session_start();

require_once __DIR__ . '/includes/image_optimizer.php';
require_once __DIR__ . '/includes/text_encoding.php';
require_once __DIR__ . '/includes/product_card.php';

$favori_ids = product_card_get_favori_ids();


// Inclusion du fichier de connexion à la BDD

// Récupérez l'ID du commerçant à partir de la session
// Récupérez l'ID de l'utilisateur depuis la variable de session
if (file_exists(__DIR__ . '/controllers/controller_commerce_users.php')) {
    require_once __DIR__ . '/controllers/controller_commerce_users.php';
}

// Meta SEO
require_once __DIR__ . '/includes/site_url.php';
$base = get_site_base_url();
$seo_title = 'Trésor Africa - Produits naturels';
$seo_description = 'Trésor Africa : produits naturels, noix, huiles, céréales et bien plus. Qualité et authenticité.';
$seo_canonical = $base . '/';
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Almarai&family=Rozha+One&family=Playfair+Display:wght@400;600;700&family=Quicksand:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/css/variables.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="/css/owl.carousel.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/owl.carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/animate.min.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/a_style.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/product-cards.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/category-carousel.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/section4-promo.css<?php echo asset_version_query(); ?>">
    <link rel="stylesheet" href="/css/popular-categories.css<?php echo asset_version_query(); ?>">
    <style>
    /* Sections produits : styles principaux dans product-cards.css (grille = section5) */
    .produit_vedete-nouveautes,
    .produit_vedete-populaires,
    .section00 .produit_vedetes {
        min-height: auto;
        padding-bottom: 48px;
    }

    .carousel-produits-outer {
        position: relative;
        width: 100%;
    }

    .btn-voir-plus-index {
        border: none;
        font-family: inherit;
    }

    .btn-voir-plus-index:disabled {
        opacity: 0.65;
        cursor: wait;
    }

    .produits-count-index {
        margin-top: 14px;
        font-size: 14px;
        color: var(--texte-muted, #5c4a3a);
        font-weight: 600;
    }

    .home-search-results {
        width: 100%;
        max-width: 1320px;
        margin: 0 auto;
        padding: 16px clamp(12px, 3vw, 20px) 8px;
        box-sizing: border-box;
        scroll-margin-top: 12px;
    }

    .home-search-results-header {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px 16px;
        margin-bottom: 14px;
    }

    .home-search-results-header h2 {
        margin: 0;
        font-size: clamp(1.1rem, 2.4vw, 1.5rem);
        color: var(--marron-logo, #502816);
        font-weight: 700;
    }

    .home-search-results-header p {
        margin: 0;
        font-size: 14px;
        color: var(--texte-muted, #5c4a3a);
    }

    .home-search-results-empty {
        text-align: center;
        padding: 28px 16px;
        color: var(--texte-muted, #5c4a3a);
        background: #fff;
        border: 1px solid rgba(80, 40, 22, 0.08);
        border-radius: 14px;
    }

    .home-search-results .articles.produits-grid-4 {
        padding-left: 0 !important;
        padding-right: 0 !important;
        max-width: 100% !important;
    }
    </style>

</head>


<body>

    <?php include('nav_bar.php') ?>


    <?php
    // Récupérer les slides depuis la base de données
    $slides = [];
    if (file_exists(__DIR__ . '/models/model_slider.php')) {
        require_once __DIR__ . '/models/model_slider.php';
        $slides_result = get_all_slides('actif'); // Récupérer uniquement les slides actifs
        $slides = is_array($slides_result) ? $slides_result : [];
    }
    ?>

    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div class="commande-perso-success"
        style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(145, 138, 68, 0.15); border-left: 4px solid var(--turquoise); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-check-circle"></i> Produit ajouté au panier avec succès.
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="commande-perso-success"
        style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(194, 102, 56, 0.15); border-left: 4px solid var(--couleur-dominante); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['commande_perso_success'])): ?>
    <div class="commande-perso-success"
        style="max-width: 600px; margin: 20px auto; padding: 15px 25px; background: rgba(145, 138, 68, 0.15); border-left: 4px solid var(--turquoise); border-radius: 8px; color: var(--titres);">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['commande_perso_success']); unset($_SESSION['commande_perso_success']); ?>
    </div>
    <?php endif; ?>

    <?php
    $recherche_home = isset($_GET['recherche']) ? trim((string) $_GET['recherche']) : '';
    $produits_recherche_home = [];
    $total_recherche_home = 0;
    if ($recherche_home !== '') {
        if (file_exists(__DIR__ . '/models/model_produits.php')) {
            require_once __DIR__ . '/models/model_produits.php';
        }
        $prix_min_home = isset($_GET['prix_min']) && $_GET['prix_min'] !== '' ? (float) $_GET['prix_min'] : null;
        $prix_max_home = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? (float) $_GET['prix_max'] : null;
        $categorie_home = isset($_GET['categorie']) && $_GET['categorie'] !== '' ? (int) $_GET['categorie'] : null;
        $tri_home = isset($_GET['tri']) && in_array($_GET['tri'], ['date', 'prix_asc', 'prix_desc', 'nom'], true) ? $_GET['tri'] : 'date';
        if (function_exists('search_produits_with_filters')) {
            $produits_recherche_home = search_produits_with_filters($recherche_home, $prix_min_home, $prix_max_home, $categorie_home, $tri_home, 0, 18);
            $total_recherche_home = function_exists('count_search_produits_with_filters')
                ? count_search_produits_with_filters($recherche_home, $prix_min_home, $prix_max_home, $categorie_home)
                : count($produits_recherche_home);
        }
    }
    ?>

    <?php if ($recherche_home !== ''): ?>
    <section class="home-search-results" id="resultats-recherche" aria-label="Résultats de recherche">
        <div class="home-search-results-header">
            <h2>Résultats pour « <?php echo e($recherche_home); ?> »</h2>
            <p><?php echo (int) $total_recherche_home; ?> produit<?php echo $total_recherche_home > 1 ? 's' : ''; ?> trouvé<?php echo $total_recherche_home > 1 ? 's' : ''; ?></p>
        </div>

        <?php if (empty($produits_recherche_home)): ?>
        <div class="home-search-results-empty">
            <p>Aucun produit ne correspond à votre recherche.</p>
            <p><a href="/produits.php">Voir tout le catalogue</a></p>
        </div>
        <?php else: ?>
        <article class="articles carousel11 produits-grid-4" id="produits-recherche-home">
            <?php foreach ($produits_recherche_home as $produit): ?>
            <?php
                $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
                    ? $produit['prix_promotion']
                    : $produit['prix'];
                $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
            ?>
            <div class="carousel" data-produit-id="<?php echo (int) $produit['id']; ?>">
                <?php render_product_card_image_block($produit, [
                    'is_favori' => in_array((int) $produit['id'], $favori_ids, true),
                ]); ?>
                <a href="produit.php?id=<?php echo (int) $produit['id']; ?>" class="product-card-link">
                    <div class="produit-content">
                        <p id="nom"><?php echo e($produit['nom'] ?? 'Produit sans nom'); ?></p>
                        <p class="prix">
                            <?php if ($has_promotion): ?>
                            <span class="span2"><?php echo number_format((float) $produit['prix'], 0, ',', ' '); ?> FCFA</span>
                            <span class="prix-promo"><?php echo number_format((float) $prix_affichage, 0, ',', ' '); ?> FCFA</span>
                            <?php else: ?>
                            <span class="prix-promo"><?php echo number_format((float) $prix_affichage, 0, ',', ' '); ?> FCFA</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </a>
                <form method="POST" action="/add-to-panier.php" class="add-to-cart-form">
                    <input type="hidden" name="produit_id" value="<?php echo (int) $produit['id']; ?>">
                    <input type="hidden" name="quantite" value="1">
                    <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php'); ?>">
                    <button type="submit" class="btn-add-cart">
                        <i class="fa-solid fa-cart-plus"></i> Ajouter au panier
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </article>
        <?php if ($total_recherche_home > count($produits_recherche_home)): ?>
        <p class="produits-count-index" style="text-align:center;">
            <a href="/produits.php?recherche=<?php echo urlencode($recherche_home); ?>">Voir tous les résultats (<?php echo (int) $total_recherche_home; ?>)</a>
        </p>
        <?php endif; ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="services-banner">
        <?php if (!empty($slides)): ?>
        <div class="slider-area owl-carousel services-banner-slider">
            <?php foreach ($slides as $slide): ?>
            <div class="slider-item">
                <?php render_upload_img('slider/' . ($slide['image'] ?? ''), $slide['titre'] ?? ''); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="services-banner-inner">
            <div class="service-item">
                <div class="service-icon"><i class="fa-solid fa-headset"></i></div>
                <h3>Service</h3>
                <p>Une équipe à votre écoute</p>
            </div>
            <div class="service-divider"></div>
            <div class="service-item">
                <div class="service-icon"><i class="fa-solid fa-shield-halved"></i></div>
                <h3>Satisfaction garantie</h3>
                <p>Produits de qualité certifiée</p>
            </div>
            <div class="service-divider"></div>
            <div class="service-item">
                <div class="service-icon"><i class="fa-solid fa-rotate-left"></i></div>
                <h3>Service après vente</h3>
                <p>Accompagnement personnalisé</p>
            </div>
            <div class="service-divider"></div>
            <div class="service-item">
                <div class="service-icon"><i class="fa-solid fa-clock"></i></div>
                <h3>Disponible 7 jours sur 7</h3>
                <p>Commandez quand vous voulez</p>
            </div>
            <div class="service-divider"></div>
            <div class="service-item">
                <div class="service-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <h3>Livraison rapide</h3>
                <p>Réception en temps record</p>
            </div>
        </div>
    </section>



    <?php
    // Récupérer les catégories depuis la base de données
    $categories = [];
    if (file_exists(__DIR__ . '/models/model_categories.php')) {
        require_once __DIR__ . '/models/model_categories.php';
        require_once __DIR__ . '/includes/category_images.php';
        $categories_result = get_all_categories_with_count();
        $categories = is_array($categories_result) ? $categories_result : [];

        if (!empty($categories)) {
            $needs_images = false;
            foreach ($categories as $cat) {
                if (empty($cat['image']) || !categorie_image_file_exists($cat['image'])) {
                    $needs_images = true;
                    break;
                }
            }
            if ($needs_images) {
                categorie_assign_missing_images();
                $categories = get_all_categories_with_count();
            }
        }
    }
    ?>

    <div class="categorie-section-wrap">
    <section class="categorie owl-carousel">
        <?php if (empty($categories)): ?>
        <div class="message-vide">
            <p style="font-size: 16px; color: var(--texte-fonce);">Aucune catégorie disponible pour le moment.</p>
        </div>
        <?php else: ?>
        <?php foreach ($categories as $categorie): ?>
        <div class="categorie-slide">
            <a href="categorie.php?id=<?php echo (int) $categorie['id']; ?>" class="categorie-link">
                <div class="categorie-circle">
                    <?php
                    $categorie_image = categorie_resolve_image($categorie);
                    if ($categorie_image !== ''):
                        render_upload_img($categorie_image, $categorie['nom'], ['class' => 'categorie-img']);
                    else:
                    ?>
                    <img class="categorie-img" src="/image/logo-brand.png" alt="<?php echo e($categorie['nom']); ?>">
                    <?php endif; ?>
                </div>
                <p class="categorie-label"><?php echo e($categorie['nom']); ?></p>
            </a>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
    </div>




    <?php
    // 12 derniers produits publiés (nouveautés)
    $produits_nouveaux = [];
    $ids_produits_nouveaux = [];
    if (file_exists(__DIR__ . '/models/model_produits.php')) {
        require_once __DIR__ . '/models/model_produits.php';
        $produits_nouveaux = get_produits_nouveautes(12);
        $ids_produits_nouveaux = array_values(array_unique(array_map('intval', array_column($produits_nouveaux, 'id'))));
    }
    $ids_produits_populaires = [];
    ?>

    <section class="produit_vedete produit_vedete-nouveautes">
        <div class="box1">
            <span></span>
            <h1>NOUVEAUX PRODUITS</h1>
            <span></span>
        </div>



        <div class="carousel-produits-outer">
            <article data-aos="fade-up" data-aos-delay="0" data-aos-duration="1000" data-aos-easing="ease-in-out"
                data-aos-mirror="true" data-aos-once="true" data-aos-anchor-placement="top-bottom"
                class="articles carousel1 carousel1-flex-mode produits-grid-4" id="carousel-nouveaux">
                <?php if (empty($produits_nouveaux)): ?>
                <!-- Message si aucun produit -->
                <div class="carousel message-vide" style="text-align: center; padding: 40px; width: 100%;">
                    <p style="color: var(--texte-fonce); font-size: 16px;">Aucun produit publié pour le moment.</p>
                </div>
                <?php else: ?>
                <?php foreach ($produits_nouveaux as $produit): ?>
                <?php
                    // Calculer le prix à afficher
                    $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
                        ? $produit['prix_promotion']
                        : $produit['prix'];
                    $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
                    $pourcentage_promo = $has_promotion ? round((($produit['prix'] - $produit['prix_promotion']) / $produit['prix']) * 100) : 0;
                    ?>
                <div class="carousel">
                    <?php render_product_card_image_block($produit, [
                        'show_nouveau_badge' => true,
                        'is_favori' => in_array((int) $produit['id'], $favori_ids, true),
                    ]); ?>
                    <a href="produit.php?id=<?php echo $produit['id']; ?>" class="product-card-link">
                        <div class="produit-content">
                            <p id="nom"><?php echo e($produit['nom'] ?? 'Produit sans nom'); ?></p>
                            <p class="prix">
                                <?php if ($has_promotion): ?>
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
                        <input type="hidden" name="return_url"
                            value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php'); ?>">
                        <button type="submit" class="btn-add-cart">
                            <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </article>
        </div>
    </section>

    <div class="brand-wave-divider" aria-hidden="true"></div>

    <?php
    if (file_exists(__DIR__ . '/models/model_section4.php')) {
        require_once __DIR__ . '/models/model_section4.php';
    }
    require_once __DIR__ . '/includes/section4_promo.php';

    $section4_config = function_exists('get_section4_config')
        ? get_section4_config()
        : section4_default_config();

    $section4_actif = ($section4_config['statut'] ?? 'actif') === 'actif';
    $banner1 = [
        'surtitre' => trim($section4_config['texte'] ?? ''),
        'titre' => trim($section4_config['titre'] ?? ''),
        'bouton' => trim($section4_config['bouton'] ?? ''),
        'lien' => $section4_config['lien'] ?? 'promo.php',
        'bg_color' => $section4_config['bg_color'] ?? '#eef6f0',
        'style_bouton' => $section4_config['style_bouton'] ?? 'solid',
        'image' => $section4_config['image_fond'] ?? null
    ];
    $banner2 = [
        'surtitre' => trim($section4_config['texte2'] ?? ''),
        'titre' => trim($section4_config['titre2'] ?? ''),
        'bouton' => trim($section4_config['bouton2'] ?? ''),
        'lien' => $section4_config['lien2'] ?? 'nouveautes.php',
        'bg_color' => $section4_config['bg_color2'] ?? '#faf7f2',
        'style_bouton' => $section4_config['style_bouton2'] ?? 'outline',
        'image' => $section4_config['image2'] ?? null
    ];
    $show_banner2 = ($section4_config['actif2'] ?? 'actif') === 'actif';
    ?>
    <?php if ($section4_actif): ?>
    <section class="section4">
        <div class="section4-promo-grid<?php echo $show_banner2 ? '' : ' section4-promo-grid--single'; ?>">
            <?php section4_promo_render_banner($banner1); ?>
            <?php if ($show_banner2): ?>
                <?php section4_promo_render_banner($banner2); ?>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php
    // Récupérer les vidéos pour le carrousel
    $videos = [];
    if (file_exists(__DIR__ . '/models/model_videos.php')) {
        require_once __DIR__ . '/models/model_videos.php';
        $videos = get_all_videos('actif');
    }
    
    // Afficher la section seulement s'il y a des vidéos
    if (!empty($videos)):
    ?>
    <section class="galerie-creations">
        <div class="galerie-creations-container">
            <header class="galerie-header">
                <span class="galerie-surtitre">Découvrez</span>
                <h2 class="galerie-titre">Nos créations</h2>
                <p class="galerie-sous-titre">Une sélection de nos réalisations en vidéo</p>
            </header>

            <div class="galerie-grid" id="videosSlider">
                <?php foreach ($videos as $index => $video): ?>
                <article class="galerie-item">
                    <div class="galerie-card">
                        <div class="galerie-video-wrapper">
                            <video class="galerie-video" controls preload="metadata" playsinline
                                <?php if (!empty($video['image_preview'])): ?>
                                poster="/upload/videos/thumbnails/<?php echo htmlspecialchars($video['image_preview']); ?>"
                                <?php endif; ?>>
                                <source src="/upload/videos/<?php echo htmlspecialchars($video['fichier_video']); ?>">
                                Votre navigateur ne supporte pas la lecture de vidéos.
                            </video>
                            <div class="galerie-play-overlay">
                                <i class="fa-solid fa-play"></i>
                            </div>
                        </div>
                        <?php if (!empty($video['titre'])): ?>
                        <div class="galerie-caption">
                            <h3><?php echo htmlspecialchars($video['titre']); ?></h3>
                        </div>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.galerie-video').forEach(function(video) {
            var overlay = video.nextElementSibling;
            if (overlay && overlay.classList.contains('galerie-play-overlay')) {
                video.addEventListener('play', function() {
                    video.classList.add('playing');
                    overlay.style.opacity = '0';
                });
                video.addEventListener('pause', function() {
                    video.classList.remove('playing');
                    overlay.style.opacity = '1';
                });
            }
        });
    });
    </script>

    <div class="brand-wave-divider" aria-hidden="true"></div>

    <?php
    // Produits populaires par catégorie (4 meilleures catégories, 4 produits chacune)
    $populaires_par_categorie = [];
    if (file_exists(__DIR__ . '/models/model_categories.php')) {
        require_once __DIR__ . '/models/model_categories.php';
    }
    if (file_exists(__DIR__ . '/models/model_visites.php')) {
        require_once __DIR__ . '/models/model_visites.php';
    }
    if (function_exists('get_top_categories') && function_exists('get_produits_populaires_by_categorie')) {
        $top_cats_populaires = get_top_categories(4);
        foreach ($top_cats_populaires as $cat_pop) {
            $cat_id = (int) ($cat_pop['id'] ?? 0);
            if ($cat_id <= 0) {
                continue;
            }
            $produits_cat = get_produits_populaires_by_categorie($cat_id, 4, $ids_produits_nouveaux);
            if (!empty($produits_cat)) {
                $populaires_par_categorie[] = [
                    'categorie' => $cat_pop,
                    'produits' => $produits_cat,
                ];
            }
        }
    }
    ?>

    <section class="produit_vedete produit_vedete-populaires">
        <h1>PRODUITS POPULAIRES</h1>

        <?php if (empty($populaires_par_categorie)): ?>
        <div class="pop-cat-section-empty">
            <p>Aucun produit publié pour le moment.</p>
        </div>
        <?php else: ?>
        <div class="pop-cat-wrap" id="pop-cat-wrap">
            <div class="pop-cat-mobile-nav">
                <div class="pop-cat-tabs-scroll">
                    <div class="pop-cat-tabs" role="tablist" aria-label="Catégories populaires">
                        <?php foreach ($populaires_par_categorie as $tab_index => $bloc_cat): ?>
                        <?php $cat_nom = e($bloc_cat['categorie']['nom'] ?? 'Catégorie'); ?>
                        <button type="button"
                            class="pop-cat-tab pop-cat-tab--<?php echo $tab_index; ?><?php echo $tab_index === 0 ? ' is-active' : ''; ?>"
                            role="tab"
                            id="pop-cat-tab-<?php echo (int) $bloc_cat['categorie']['id']; ?>"
                            aria-selected="<?php echo $tab_index === 0 ? 'true' : 'false'; ?>"
                            aria-controls="pop-cat-panel-<?php echo (int) $bloc_cat['categorie']['id']; ?>"
                            data-pop-index="<?php echo $tab_index; ?>">
                            <?php echo $cat_nom; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="pop-cat-dots" role="tablist" aria-label="Navigation catégories">
                    <?php foreach ($populaires_par_categorie as $dot_index => $bloc_cat): ?>
                    <?php $dot_nom = e($bloc_cat['categorie']['nom'] ?? 'Catégorie'); ?>
                    <button type="button"
                        class="pop-cat-dot pop-cat-dot--<?php echo $dot_index; ?><?php echo $dot_index === 0 ? ' is-active' : ''; ?>"
                        role="tab"
                        aria-label="<?php echo $dot_nom; ?>"
                        aria-selected="<?php echo $dot_index === 0 ? 'true' : 'false'; ?>"
                        data-pop-index="<?php echo $dot_index; ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pop-cat-cards-grid">
                <div class="pop-cat-panel-viewport">
                <?php foreach ($populaires_par_categorie as $panel_index => $bloc_cat): ?>
                <?php
                    $cat = $bloc_cat['categorie'];
                    $cat_id = (int) ($cat['id'] ?? 0);
                    $cat_nom = e($cat['nom'] ?? 'Catégorie');
                ?>
                <div class="pop-cat-column pop-cat-column--<?php echo $panel_index; ?><?php echo $panel_index === 0 ? ' is-active' : ''; ?>"
                    role="tabpanel"
                    id="pop-cat-panel-<?php echo $cat_id; ?>"
                    aria-labelledby="pop-cat-tab-<?php echo $cat_id; ?>"
                    data-pop-index="<?php echo $panel_index; ?>"
                    <?php echo $panel_index === 0 ? '' : 'hidden'; ?>>
                    <div class="pop-cat-card">
                        <a href="categorie.php?id=<?php echo $cat_id; ?>" class="pop-cat-card-heading">
                            <h2 class="pop-cat-card-title"><?php echo $cat_nom; ?></h2>
                            <span class="pop-cat-chevron" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></span>
                        </a>
                        <div class="pop-cat-grid">
                            <?php foreach ($bloc_cat['produits'] as $cell_index => $produit_pop): ?>
                            <?php
                                $img_path = $produit_pop['image_principale'] ?? '';
                                $img_url = image_upload_url($img_path !== '' ? $img_path : '', '/image/produit1.jpg');
                                $produit_nom = e($produit_pop['nom'] ?? 'Produit');
                            ?>
                            <a href="produit.php?id=<?php echo (int) $produit_pop['id']; ?>" class="pop-cat-cell"
                                title="<?php echo $produit_nom; ?>">
                                <span class="pop-cat-cell-img pop-cat-cell-img--<?php echo (int) $cell_index; ?>">
                                    <img src="<?php echo htmlspecialchars($img_url); ?>"
                                        alt="<?php echo $produit_nom; ?>"
                                        loading="lazy" decoding="async"
                                        onerror="this.onerror=null;this.src='/image/produit1.jpg';">
                                </span>
                                <span class="pop-cat-cell-label"><?php echo $produit_nom; ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <div class="brand-wave-divider" aria-hidden="true"></div>

    <?php
    // Récupérer les 4 catégories les plus populaires (visites + commandes)
    $top_categories = [];
    if (function_exists('get_top_categories')) {
        $top_categories = get_top_categories(4);
    } elseif (file_exists(__DIR__ . '/models/model_categories.php')) {
        require_once __DIR__ . '/models/model_categories.php';
        $top_categories = get_top_categories(4);
    }
    ?>

    <section class="section5">
        <h1>Top Categorie</h1>
        <div class="container">
            <?php if (empty($top_categories)): ?>
            <!-- Message si aucune catégorie -->
            <div class="message-vide" style="text-align: center; padding: 40px; color: var(--texte-fonce);">
                <p>Aucune catégorie disponible pour le moment.</p>
            </div>
            <?php else: ?>
            <?php foreach ($top_categories as $categorie): ?>
            <?php
                    $categorie_image_path = '/image/logo-brand.png';
                    $resolved = categorie_resolve_image($categorie);
                    if ($resolved !== '') {
                        $categorie_image_path = '/upload/' . $resolved;
                    }
                    ?>
            <div class="slider">
                <img src="<?php echo $categorie_image_path; ?>" alt="<?php echo htmlspecialchars($categorie['nom']); ?>"
                    onerror="this.src='/image/produit1.jpg'">
                <div class="box">
                    <h4><?php echo htmlspecialchars(strtoupper($categorie['nom'])); ?></h4>
                    <a href="categorie.php?id=<?php echo $categorie['id']; ?>">Voir cette categorie ></a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <div class="brand-wave-divider" aria-hidden="true"></div>

    <?php
    // Tous les produits actifs, ordre aléatoire à chaque chargement de page
    $produits_tous = [];
    $catalogue_order_ids = [];
    $total_produits = 0;
    $produits_par_page = 18;
    if (file_exists(__DIR__ . '/models/model_produits.php')) {
        require_once __DIR__ . '/models/model_produits.php';
        $catalogue_order_ids = shuffle_produit_ids(get_all_actif_produit_ids());
        $total_produits = count($catalogue_order_ids);
        $first_batch_ids = array_slice($catalogue_order_ids, 0, $produits_par_page);
        $produits_tous = get_produits_by_ids($first_batch_ids);
    }
    ?>

    <section class="section00">
        <section class="produit_vedetes">
            <div class="box1">
                <h1>Tous nos produits</h1>
            </div>

            <article data-aos="fade-up" data-aos-delay="0" data-aos-duration="1000" data-aos-easing="ease-in-out"
                data-aos-mirror="true" data-aos-once="true" data-aos-anchor-placement="top-bottom"
                class="articles carousel11 produits-grid-4" id="produits-container">
                <?php if (empty($produits_tous)): ?>
                <!-- Message si aucun produit -->
                <div class="message-vide"
                    style="text-align: center; padding: 40px; color: var(--texte-fonce); width: 100%;">
                    <p style="font-size: 16px;">Aucun produit publié pour le moment.</p>
                </div>
                <?php else: ?>
                <?php foreach ($produits_tous as $produit): ?>
                <?php
                        // Calculer le prix à afficher
                        $prix_affichage = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix']
                            ? $produit['prix_promotion']
                            : $produit['prix'];
                        $has_promotion = !empty($produit['prix_promotion']) && $produit['prix_promotion'] < $produit['prix'];
                        $pourcentage_promo = $has_promotion ? round((($produit['prix'] - $produit['prix_promotion']) / $produit['prix']) * 100) : 0;
                        ?>
                <div class="carousel" data-produit-id="<?php echo $produit['id']; ?>">
                    <?php render_product_card_image_block($produit, [
                        'is_favori' => in_array((int) $produit['id'], $favori_ids, true),
                    ]); ?>
                    <a href="produit.php?id=<?php echo $produit['id']; ?>" class="product-card-link">
                        <div class="produit-content">
                            <p id="nom"><?php echo e($produit['nom'] ?? 'Produit sans nom'); ?></p>
                            <p class="prix">
                                <?php if ($has_promotion): ?>
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
                        <input type="hidden" name="return_url"
                            value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/index.php'); ?>">
                        <button type="submit" class="btn-add-cart">
                            <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </article>

            <?php if (!empty($produits_tous) && $total_produits > $produits_par_page): ?>
            <div class="voir-tous-produits-wrapper" id="index-voir-plus-wrapper">
                <button type="button" id="btn-index-voir-plus" class="btn-voir-tous-produits btn-voir-plus-index">
                    <i class="fas fa-chevron-down"></i> Voir plus de produits
                </button>
                <p class="produits-count-index">
                    Affichés : <span id="index-count-actuel"><?php echo min(count($produits_tous), $produits_par_page); ?></span>
                    / <?php echo $total_produits; ?> produits
                </p>
            </div>
            <?php endif; ?>
        </section>
    </section>




    <?php include('footer.php') ?>

    <?php if (!empty($produits_tous) && $total_produits > $produits_par_page): ?>
    <script>
    (function() {
        var offsetActuel = <?php echo (int) min(count($produits_tous), $produits_par_page); ?>;
        var limit = <?php echo (int) $produits_par_page; ?>;
        var totalProduits = <?php echo (int) $total_produits; ?>;
        var catalogueOrder = <?php echo json_encode(array_values($catalogue_order_ids)); ?>;
        var btn = document.getElementById('btn-index-voir-plus');
        var container = document.getElementById('produits-container');
        var countActuel = document.getElementById('index-count-actuel');
        var wrapper = document.getElementById('index-voir-plus-wrapper');

        if (!btn || !container) {
            return;
        }

        function formatNumber(num) {
            return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text == null ? '' : text;
            return div.innerHTML;
        }

        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';

            var nextIds = catalogueOrder.slice(offsetActuel, offsetActuel + limit);
            if (!nextIds.length) {
                if (wrapper) {
                    wrapper.style.display = 'none';
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-chevron-down"></i> Voir plus de produits';
                return;
            }

            var apiUrl = 'api/get_produits.php?ids=' + encodeURIComponent(nextIds.join(','));

            fetch(apiUrl)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && data.produits.length > 0) {
                        data.produits.forEach(function(produit) {
                            var div = document.createElement('div');
                            div.className = 'carousel';
                            div.setAttribute('data-produit-id', produit.id);

                            var prixHTML = '';
                            if (produit.has_promotion) {
                                prixHTML = '<span class="span2">' + formatNumber(produit.prix) + ' FCFA</span>' +
                                    '<span class="prix-promo">' + formatNumber(produit.prix_affichage) + ' FCFA</span>';
                            } else {
                                prixHTML = formatNumber(produit.prix_affichage) + '<span class="span1"> FCFA</span>';
                            }

                            var returnUrl = (window.location.pathname + window.location.search)
                                .replace(/&/g, '&amp;')
                                .replace(/"/g, '&quot;');

                            var actionsHtml = window.buildProductCardActionsHtml
                                ? window.buildProductCardActionsHtml(
                                    produit.id,
                                    produit.nom,
                                    produit.share_url || ('/produit.php?id=' + produit.id),
                                    !!produit.is_favori,
                                    window.location.pathname + window.location.search,
                                    !!(window.ProductCardConfig && window.ProductCardConfig.isLoggedIn)
                                )
                                : '';

                            div.innerHTML =
                                '<a href="produit.php?id=' + produit.id + '" class="product-card-link">' +
                                    '<div class="produit-content">' +
                                        '<p id="nom">' + escapeHtml(produit.nom) + '</p>' +
                                        '<p class="prix">' + prixHTML + '</p>' +
                                    '</div>' +
                                '</a>' +
                                '<form method="POST" action="/add-to-panier.php" class="add-to-cart-form">' +
                                    '<input type="hidden" name="produit_id" value="' + produit.id + '">' +
                                    '<input type="hidden" name="quantite" value="1">' +
                                    '<input type="hidden" name="return_url" value="' + returnUrl + '">' +
                                    '<button type="submit" class="btn-add-cart">' +
                                        '<i class="fa-solid fa-cart-shopping"></i> Ajouter au panier' +
                                    '</button>' +
                                '</form>';

                            var imageWrapper = document.createElement('div');
                            imageWrapper.className = 'image-wrapper';
                            imageWrapper.innerHTML = actionsHtml +
                                '<a href="produit.php?id=' + produit.id + '" class="product-card-image-link">' +
                                    '<img src="' + escapeHtml(produit.image_url || ('/upload/' + produit.image_principale)) + '" ' +
                                    'alt="' + escapeHtml(produit.nom) + '" onerror="this.src=\'/image/produit1.jpg\'">' +
                                '</a>';
                            div.insertBefore(imageWrapper, div.firstChild);

                            container.appendChild(div);
                        });

                        offsetActuel += data.produits.length;
                        if (countActuel) {
                            countActuel.textContent = offsetActuel;
                        }

                        if (offsetActuel >= totalProduits) {
                            if (wrapper) {
                                wrapper.style.display = 'none';
                            }
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-chevron-down"></i> Voir plus de produits';
                        }
                    } else if (wrapper) {
                        wrapper.style.display = 'none';
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-chevron-down"></i> Voir plus de produits';
                    alert('Une erreur est survenue lors du chargement des produits.');
                });
        });
    })();
    </script>
    <?php endif; ?>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/owl.carousel.js"></script>
    <script src="/js/owl.animate.js"></script>
    <script src="/js/owl.autoplay.js"></script>
    <?php if (!empty($populaires_par_categorie)): ?>
    <script src="/js/pop-categories.js<?php echo asset_version_query(); ?>"></script>
    <?php endif; ?>

    <script>
    $(document).ready(function() {

        $('.slider1').owlCarousel({
            items: 2,
            loop: true,
            dots: true,
            autoplay: true,
            autoplayTimeout: 4000,
            animateOut: 'slideOutDown',
            animateIn: 'flipInX',
            smartSpeed: 400,
            stagePadding: 0,
            nav: true,
            navText: ['<i class="fa-solid fa-chevron-left"></i>',
                '<i class="fa-solid fa-chevron-right"></i>'
            ]
        });
        var carousel2 = $('.slider1').owlCarousel();
        $('.owl-next2').click(function() {
            carousel2.trigger('next.owl.carousel');
        })
        $('.owl-prev2').click(function() {
            carousel2.trigger('prev.owl.carousel');
        })

        if ($('.slider-area').length) {
            $('.slider-area').owlCarousel({
                items: 1,
                loop: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 6000,
                animateOut: 'slideOutDown',
                animateIn: 'flipInX',
                smartSpeed: 800,
                stagePadding: 1,
                nav: true,
                navText: ['<i class="fa-solid fa-chevron-left"></i>',
                    '<i class="fa-solid fa-chevron-right"></i>'
                ]
            });
        }
        var carousel2 = $('.carousel2').owlCarousel();
        $('.owl-next2').click(function() {
            carousel2.trigger('next.owl.carousel');
        })
        $('.owl-prev2').click(function() {
            carousel2.trigger('prev.owl.carousel');
        })


        // Carrousel catégories — mobile : 3 + ½ visible, défilement auto lent + swipe manuel
        var $catCarousel = $('.categorie');
        if ($catCarousel.length) {
            var catItemCount = $catCarousel.children('.categorie-slide').length;
            var catMobileQuery = window.matchMedia('(max-width: 576px)');

            function isCatMobileLayout() {
                return catMobileQuery.matches;
            }

            var catWidthUpdating = false;

            function applyMobileCatSlotWidth() {
                if (!$catCarousel.hasClass('owl-loaded') || !isCatMobileLayout() || catWidthUpdating) {
                    return;
                }
                var outerW = $catCarousel.find('.owl-stage-outer').innerWidth();
                if (outerW <= 0) {
                    return;
                }
                var slotW = Math.round(outerW / 3.5);
                catWidthUpdating = true;
                $catCarousel.find('.owl-item').css('width', slotW + 'px');
                $catCarousel.trigger('refresh.owl.carousel');
                catWidthUpdating = false;
            }

            $catCarousel.owlCarousel({
                items: 8,
                loop: false,
                rewind: false,
                dots: false,
                autoplay: false,
                smartSpeed: 450,
                stagePadding: 0,
                margin: 16,
                slideBy: 1,
                nav: catItemCount > 8,
                mouseDrag: catItemCount > 8,
                touchDrag: catItemCount > 8,
                navText: [
                    '<i class="fa-solid fa-chevron-left"></i>',
                    '<i class="fa-solid fa-chevron-right"></i>'
                ],
                responsive: {
                    0: {
                        autoWidth: true,
                        margin: 8,
                        slideBy: 1,
                        loop: catItemCount > 4,
                        rewind: false,
                        autoplay: catItemCount > 4,
                        autoplayTimeout: 5000,
                        autoplaySpeed: 1600,
                        autoplayHoverPause: true,
                        smartSpeed: 1600,
                        nav: false,
                        mouseDrag: true,
                        touchDrag: true
                    },
                    577: {
                        autoWidth: false,
                        items: 4,
                        margin: 12,
                        loop: false,
                        autoplay: false,
                        nav: catItemCount > 4,
                        mouseDrag: catItemCount > 4,
                        touchDrag: catItemCount > 4
                    },
                    768: {
                        items: 5,
                        margin: 14,
                        nav: catItemCount > 5,
                        mouseDrag: catItemCount > 5,
                        touchDrag: catItemCount > 5
                    },
                    992: {
                        items: 6,
                        margin: 16,
                        nav: catItemCount > 6,
                        mouseDrag: catItemCount > 6,
                        touchDrag: catItemCount > 6
                    },
                    1200: {
                        items: 8,
                        margin: 20,
                        nav: catItemCount > 8,
                        mouseDrag: catItemCount > 8,
                        touchDrag: catItemCount > 8
                    }
                }
            });

            $catCarousel.on('initialized.owl.carousel', function () {
                applyMobileCatSlotWidth();
            });

            var catResizeTimer;
            $(window).on('resize', function () {
                clearTimeout(catResizeTimer);
                catResizeTimer = setTimeout(function () {
                    if (!isCatMobileLayout()) {
                        $catCarousel.find('.owl-item').css('width', '');
                    }
                    applyMobileCatSlotWidth();
                }, 180);
            });

            if (typeof catMobileQuery.addEventListener === 'function') {
                catMobileQuery.addEventListener('change', function () {
                    if (!isCatMobileLayout()) {
                        $catCarousel.find('.owl-item').css('width', '');
                    }
                    applyMobileCatSlotWidth();
                });
            }
        }
        var carousel2 = $('.carousel2').owlCarousel();
        $('.owl-next2').click(function() {
            carousel2.trigger('next.owl.carousel');
        })
        $('.owl-prev2').click(function() {
            carousel2.trigger('prev.owl.carousel');
        })


    });
    </script>

    <script>
    // ..
    AOS.init();
    </script>

    <script>
    // Slider vidéo simple en JavaScript vanilla
    document.addEventListener('DOMContentLoaded', function() {
        var slider = document.getElementById('videosSlider');
        var prevBtn = document.getElementById('videosPrev');
        var nextBtn = document.getElementById('videosNext');
        var dotsContainer = document.getElementById('videosDots');
        var autoplayInterval;
        var autoplayDelay = 8000; // 8 secondes

        if (!slider) {
            return; // Pas de slider, ne rien faire
        }

        var cards = slider.querySelectorAll('.video-card');
        if (cards.length === 0) {
            return; // Pas de vidéos
        }

        var currentIndex = 0;
        var itemsPerView = 1; // Par défaut mobile
        var dots = [];

        // Fonction pour déterminer le nombre d'éléments visibles
        function getItemsPerView() {
            var width = window.innerWidth;
            if (width >= 992) {
                return 3; // Grand écran : 3 vidéos
            } else if (width >= 768) {
                return 2; // Tablette : 2 vidéos
            }
            return 1; // Mobile : 1 vidéo
        }

        // Fonction pour créer les dots
        function createDots() {
            if (!dotsContainer) return;

            itemsPerView = getItemsPerView();
            var totalPages = Math.ceil(cards.length / itemsPerView);

            dotsContainer.innerHTML = '';
            dots = [];

            for (var i = 0; i < totalPages; i++) {
                var dot = document.createElement('span');
                dot.className = 'dot';
                if (i === 0) {
                    dot.classList.add('active');
                }
                dot.setAttribute('data-page', i);
                dot.addEventListener('click', function() {
                    var page = parseInt(this.getAttribute('data-page'));
                    currentIndex = page * itemsPerView;
                    updateSlider();
                    stopAutoplay();
                    startAutoplay();
                });
                dotsContainer.appendChild(dot);
                dots.push(dot);
            }
        }

        // Fonction pour calculer le nombre de slides possibles
        function getMaxIndex() {
            itemsPerView = getItemsPerView();
            return Math.max(0, cards.length - itemsPerView);
        }

        // Fonction pour mettre à jour la position du slider
        function updateSlider() {
            var maxIndex = getMaxIndex();
            if (currentIndex > maxIndex) {
                currentIndex = maxIndex;
            }

            if (cards.length === 0) return;

            // Calculer la translation en fonction de la largeur des cartes
            var cardWidth = cards[0].offsetWidth;
            var gap = 20;
            var translateX = -(currentIndex * (cardWidth + gap));

            slider.style.transform = 'translateX(' + translateX + 'px)';

            // Mettre à jour les dots
            var dotIndex = Math.floor(currentIndex / itemsPerView);
            dots.forEach(function(dot, index) {
                dot.classList.remove('active');
                if (index === dotIndex) {
                    dot.classList.add('active');
                }
            });

            // Afficher/masquer les boutons selon la position
            if (prevBtn) {
                prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
            }
            if (nextBtn) {
                nextBtn.style.display = currentIndex >= maxIndex ? 'none' : 'flex';
            }
        }

        function nextSlide() {
            var maxIndex = getMaxIndex();
            if (currentIndex < maxIndex) {
                currentIndex += itemsPerView;
            } else {
                currentIndex = 0; // Retour au début
            }
            updateSlider();
        }

        function prevSlide() {
            var maxIndex = getMaxIndex();
            if (currentIndex > 0) {
                currentIndex -= itemsPerView;
                if (currentIndex < 0) {
                    currentIndex = maxIndex; // Aller à la fin
                }
            } else {
                currentIndex = maxIndex; // Aller à la fin
            }
            updateSlider();
        }

        function startAutoplay() {
            autoplayInterval = setInterval(function() {
                nextSlide();
            }, autoplayDelay);
        }

        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
            }
        }

        // Événements pour les boutons
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                stopAutoplay();
                startAutoplay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                stopAutoplay();
                startAutoplay();
            });
        }

        // Pause autoplay au survol
        var sliderWrapper = document.querySelector('.videos-slider-wrapper');
        if (sliderWrapper) {
            sliderWrapper.addEventListener('mouseenter', stopAutoplay);
            sliderWrapper.addEventListener('mouseleave', startAutoplay);
        }

        // Gérer le redimensionnement de la fenêtre
        var resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                currentIndex = 0;
                createDots();
                updateSlider();
            }, 250);
        });

        // Initialiser
        createDots();
        updateSlider();
        if (cards.length > getItemsPerView()) {
            startAutoplay();
        }
    });
    </script>

</body>

</html>