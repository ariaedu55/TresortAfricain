<?php
/**
 * API suggestions de recherche (produits + catégories) pour la barre de navigation
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../models/model_produits.php';
require_once __DIR__ . '/../models/model_categories.php';
require_once __DIR__ . '/../includes/image_optimizer.php';

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;

if ($limit < 1 || $limit > 12) {
    $limit = 8;
}

if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([
        'success' => true,
        'q' => $q,
        'produits' => [],
        'categories' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$produits_raw = search_produits_suggestions($q, $limit);
$categories_raw = search_categories_suggestions($q, 3);

$produits = [];
foreach ($produits_raw as $produit) {
    $prix = (float) ($produit['prix'] ?? 0);
    $prix_promo = isset($produit['prix_promotion']) && $produit['prix_promotion'] !== null && $produit['prix_promotion'] !== ''
        ? (float) $produit['prix_promotion']
        : null;
    $has_promo = $prix_promo !== null && $prix_promo > 0 && $prix_promo < $prix;
    $prix_affichage = $has_promo ? $prix_promo : $prix;

    $produits[] = [
        'id' => (int) ($produit['id'] ?? 0),
        'nom' => (string) ($produit['nom'] ?? ''),
        'categorie_nom' => (string) ($produit['categorie_nom'] ?? ''),
        'poids' => (string) ($produit['poids'] ?? ''),
        'prix' => $prix,
        'prix_affichage' => $prix_affichage,
        'has_promotion' => $has_promo,
        'image_url' => image_upload_url($produit['image_principale'] ?? '', '/image/produit1.jpg'),
        'url' => '/produit.php?id=' . (int) ($produit['id'] ?? 0),
    ];
}

$categories = [];
foreach ($categories_raw as $categorie) {
    $img = trim((string) ($categorie['image'] ?? ''));
    $categories[] = [
        'id' => (int) ($categorie['id'] ?? 0),
        'nom' => (string) ($categorie['nom'] ?? ''),
        'image_url' => $img !== '' ? image_upload_url($img, '/image/logo-brand.png') : '/image/logo-brand.png',
        'url' => '/categorie.php?id=' . (int) ($categorie['id'] ?? 0),
    ];
}

echo json_encode([
    'success' => true,
    'q' => $q,
    'produits' => $produits,
    'categories' => $categories,
], JSON_UNESCAPED_UNICODE);
