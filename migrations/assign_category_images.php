<?php
/**
 * Assigne des vignettes aux catégories sans image
 * Usage : php migrations/assign_category_images.php
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/category_images.php';

if (!isset($db) || !($db instanceof PDO)) {
    fwrite(STDERR, "Connexion base de données indisponible.\n");
    exit(1);
}

$stats = categorie_assign_missing_images($db);

echo "Catégories mises à jour : {$stats['updated']}\n";
echo "Depuis produit : {$stats['from_product']}\n";
echo "Depuis pool produits : {$stats['from_pool']}\n";
echo "Vignettes générées : {$stats['generated']}\n";
echo "Déjà avec image : {$stats['skipped']}\n";

exit(0);
