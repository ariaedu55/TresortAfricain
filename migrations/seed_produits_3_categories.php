<?php
/**
 * Ajoute 10 produits de démo dans 3 catégories distinctes.
 * Usage : php migrations/seed_produits_3_categories.php
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../models/model_produits.php';

$images = glob(__DIR__ . '/../upload/produits/*.webp');
sort($images);
$image_paths = array_map(function ($path) {
    return 'produits/' . basename($path);
}, $images);

if (empty($image_paths)) {
    $image_paths = [null];
}

$produits = [
    // Huile de coco (4)
    [
        'categorie_id' => 27,
        'nom' => 'Huile de coco vierge 250 ml',
        'description' => 'Huile de coco vierge extraite à froid, idéale pour la cuisine et les soins.',
        'prix' => 2500,
        'prix_promotion' => 2200,
        'stock' => 25,
        'poids' => '250ml',
        'unite' => 'bouteille',
    ],
    [
        'categorie_id' => 27,
        'nom' => 'Huile de coco vierge 500 ml',
        'description' => 'Huile de coco pure, sans additifs, produite localement.',
        'prix' => 4500,
        'prix_promotion' => null,
        'stock' => 18,
        'poids' => '500ml',
        'unite' => 'bouteille',
    ],
    [
        'categorie_id' => 27,
        'nom' => 'Huile de coco bio 1 L',
        'description' => 'Grande format économique, 100 % naturelle et bio.',
        'prix' => 7500,
        'prix_promotion' => 6800,
        'stock' => 12,
        'poids' => '1L',
        'unite' => 'bouteille',
    ],
    [
        'categorie_id' => 27,
        'nom' => 'Huile de coco pression à froid',
        'description' => 'Extraction douce pour préserver les nutriments essentiels.',
        'prix' => 3200,
        'prix_promotion' => null,
        'stock' => 20,
        'poids' => '350ml',
        'unite' => 'bouteille',
    ],
    // Miel pur (3)
    [
        'categorie_id' => 43,
        'nom' => 'Miel pur de cashew 250 g',
        'description' => 'Miel sauvage récolté dans les savanes, goût doux et floral.',
        'prix' => 3500,
        'prix_promotion' => 3100,
        'stock' => 30,
        'poids' => '250g',
        'unite' => 'pot',
    ],
    [
        'categorie_id' => 43,
        'nom' => 'Miel pur sauvage 500 g',
        'description' => 'Miel 100 % naturel, non pasteurisé, riche en enzymes.',
        'prix' => 6200,
        'prix_promotion' => null,
        'stock' => 22,
        'poids' => '500g',
        'unite' => 'pot',
    ],
    [
        'categorie_id' => 43,
        'nom' => 'Miel pur de mangrove 750 g',
        'description' => 'Miel aux notes boisées, récolte artisanale.',
        'prix' => 8900,
        'prix_promotion' => 7900,
        'stock' => 15,
        'poids' => '750g',
        'unite' => 'pot',
    ],
    // Poudre de moringa (3)
    [
        'categorie_id' => 38,
        'nom' => 'Poudre de moringa 100 g',
        'description' => 'Feuilles de moringa séchées et moulues, source de vitamines.',
        'prix' => 1800,
        'prix_promotion' => null,
        'stock' => 40,
        'poids' => '100g',
        'unite' => 'sachet',
    ],
    [
        'categorie_id' => 38,
        'nom' => 'Poudre de moringa 250 g',
        'description' => 'Super-aliment naturel pour smoothies, jus et tisanes.',
        'prix' => 3200,
        'prix_promotion' => 2800,
        'stock' => 35,
        'poids' => '250g',
        'unite' => 'sachet',
    ],
    [
        'categorie_id' => 38,
        'nom' => 'Poudre de moringa bio 500 g',
        'description' => 'Format familial, culture biologique sans pesticides.',
        'prix' => 5500,
        'prix_promotion' => null,
        'stock' => 28,
        'poids' => '500g',
        'unite' => 'sachet',
    ],
];

$inserted = 0;
$errors = [];

foreach ($produits as $index => $item) {
    $item['image_principale'] = $image_paths[$index % count($image_paths)];
    $item['statut'] = 'actif';

    $id = create_produit($item);
    if ($id) {
        $inserted++;
        echo "OK produit #{$id} — {$item['nom']} (cat. {$item['categorie_id']})\n";
    } else {
        $errors[] = $item['nom'];
        echo "ERREUR — {$item['nom']}\n";
    }
}

echo "\nRésumé : {$inserted}/" . count($produits) . " produits ajoutés.\n";

if (!empty($errors)) {
    exit(1);
}
