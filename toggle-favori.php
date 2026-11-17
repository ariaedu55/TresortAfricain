<?php
/**
 * Ajout / retrait d'un produit des favoris
 */
session_start();

require_once __DIR__ . '/models/model_favoris.php';

$return_url = isset($_POST['return_url']) && $_POST['return_url'] !== ''
    ? (string) $_POST['return_url']
    : '/index.php';

$is_ajax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);

function toggle_favori_json_response($payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) {
        toggle_favori_json_response([
            'ok' => false,
            'error' => 'login_required',
            'message' => 'Connectez-vous pour gérer vos favoris.',
            'login_url' => '/user/connexion.php?redirect=' . urlencode('toggle-favori'),
        ], 401);
    }

    $redirect = '/user/connexion.php?redirect=' . urlencode('toggle-favori');
    header('Location: ' . $redirect . '&return=' . urlencode($return_url));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['produit_id'])) {
    if ($is_ajax) {
        toggle_favori_json_response(['ok' => false, 'error' => 'invalid_request'], 400);
    }
    header('Location: ' . $return_url);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$produit_id = (int) $_POST['produit_id'];

if ($produit_id <= 0) {
    if ($is_ajax) {
        toggle_favori_json_response(['ok' => false, 'error' => 'invalid_product'], 400);
    }
    header('Location: ' . $return_url);
    exit;
}

$was_favori = is_favori($user_id, $produit_id);

if ($was_favori) {
    $ok = remove_favori($user_id, $produit_id);
    $is_favori = false;
    $action = 'removed';
} else {
    $ok = add_favori($user_id, $produit_id);
    $is_favori = true;
    $action = 'added';
}

if ($is_ajax) {
    if (!$ok) {
        toggle_favori_json_response(['ok' => false, 'error' => 'server_error'], 500);
    }

    toggle_favori_json_response([
        'ok' => true,
        'is_favori' => $is_favori,
        'action' => $action,
        'produit_id' => $produit_id,
        'message' => $is_favori ? 'Ajouté à vos favoris' : 'Retiré de vos favoris',
    ]);
}

$separator = (strpos($return_url, '?') !== false) ? '&' : '?';
$param = $ok ? ('favori=' . $action) : 'favori=error';
header('Location: ' . $return_url . $separator . $param);
exit;
