<?php
/**
 * Affichage d'une facture de devis (admin, design identique à facture commande)
 */
session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_email'])) {
    header('Location: ../login.php');
    exit;
}

$facture_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($facture_id <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../../models/model_factures_devis.php';
require_once __DIR__ . '/../../models/model_devis.php';

$facture = get_facture_devis_by_id($facture_id);
if (!$facture) {
    header('Location: index.php');
    exit;
}

$token = $facture['token'] ?? null;
if (empty($token)) {
    $token = ensure_facture_devis_token($facture_id);
    if ($token) {
        $facture = get_facture_devis_by_id($facture_id);
    }
}

$devis = get_devis_by_id($facture['devis_id']);
$produits = get_produits_by_devis($facture['devis_id']);
$produits = is_array($produits) ? $produits : [];

// Construire une structure compatible avec facture_content (commande-like)
$commande = [
    'user_prenom' => $devis['client_prenom'] ?? '',
    'user_nom' => $devis['client_nom'] ?? '',
    'user_telephone' => $devis['client_telephone'] ?? '',
    'telephone_livraison' => $devis['client_telephone'] ?? '',
    'adresse_livraison' => $devis['adresse_livraison'] ?? '',
    'notes' => $devis['notes'] ?? '—',
    'frais_livraison' => $devis['frais_livraison'] ?? 0,
    'numero_commande' => $devis['numero_devis'] ?? ''
];

$client_nom = trim(($devis['client_prenom'] ?? '') . ' ' . ($devis['client_nom'] ?? ''));
$client_telephone = $devis['client_telephone'] ?? '';
$adresse_livraison = $devis['adresse_livraison'] ?? '';

$mois = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$d_facture = strtotime($facture['date_facture']);
$date_facture_aff = date('j', $d_facture) . ' ' . $mois[(int) date('n', $d_facture) - 1] . ' ' . date('Y', $d_facture);

$fe = require __DIR__ . '/../../includes/facture_entreprise.php';
$entreprise_nom = $fe['nom'];
$entreprise_rc = $fe['rc'];
$entreprise_ninea = $fe['ninea'];
$entreprise_adresse = $fe['adresse'];
$entreprise_tel1 = $fe['tel1'];
$entreprise_tel2 = $fe['tel2'];
$entreprise_site = $fe['site'];
$entreprise_email = $fe['email'];

$is_public = false;
$facture_back_url = 'details.php?id=' . $devis['id'];
$facture_back_label = 'Retour au devis';
require __DIR__ . '/../../includes/facture_content.php';