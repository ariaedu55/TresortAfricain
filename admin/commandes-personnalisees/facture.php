<?php
/**
 * Affichage d'une facture de commande personnalisée (Admin)
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

require_once __DIR__ . '/../../includes/asset_version.php';
require_once __DIR__ . '/../../models/model_factures_personnalisees.php';
require_once __DIR__ . '/../../models/model_commandes_personnalisees.php';

$facture = get_facture_personnalisee_by_id($facture_id);
if (!$facture) {
    header('Location: index.php');
    exit;
}

$token = $facture['token'] ?? null;
if (empty($token)) {
    $token = ensure_facture_personnalisee_token($facture_id);
    if ($token) {
        $facture = get_facture_personnalisee_by_id($facture_id);
    }
}

$cp = get_commande_personnalisee_by_id($facture['commande_personnalisee_id']);
if (!$cp) {
    header('Location: index.php');
    exit;
}

$client_nom = trim(($cp['prenom'] ?? '') . ' ' . ($cp['nom'] ?? ''));
$client_telephone = $cp['telephone'] ?? '';

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
require __DIR__ . '/../../includes/facture_cp_content.php';