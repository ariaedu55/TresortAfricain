<?php
/**
 * Contrôleur pour la gestion de la section4
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../models/model_section4.php';
require_once __DIR__ . '/../includes/image_optimizer.php';

/**
 * Traite l'upload d'une image section4
 * @param array $file
 * @param string $prefix
 * @return array
 */
function upload_section4_image_field($file, $prefix = 'section4_') {
    $upload_dir = __DIR__ . '/../upload/section4/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $max_size = 50 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        return ['success' => false, 'filename' => null, 'message' => 'Le fichier est trop volumineux. Taille maximale: 50MB'];
    }

    if (!image_optimizer_is_allowed_upload($file['tmp_name'], mime_content_type($file['tmp_name']))) {
        return ['success' => false, 'filename' => null, 'message' => 'Type de fichier non autorisé. Formats acceptés: JPEG, JPG, PNG, GIF, WEBP'];
    }

    $result = image_optimizer_process_uploaded_file($file['tmp_name'], $upload_dir, [
        'filename_prefix' => $prefix,
        'max_width' => 1200,
        'max_height' => 800,
        'quality' => 82,
    ]);

    if (!empty($result['success'])) {
        return ['success' => true, 'filename' => $result['filename'], 'message' => 'Image uploadée avec succès'];
    }

    return ['success' => false, 'filename' => null, 'message' => 'Erreur lors de l\'upload du fichier'];
}

/**
 * Gère l'upload de l'image de fond de la section4 (rétrocompatibilité)
 * @param array $file
 * @return array
 */
function upload_section4_image($file) {
    return upload_section4_image_field($file, 'section4_');
}

/**
 * Traite un champ image avec conservation de l'existant
 * @param string $field_name
 * @param string|null $current_filename
 * @param string $prefix
 * @return array ['success'=>bool,'filename'=>string|null,'message'=>string]
 */
function process_section4_image_upload($field_name, $current_filename, $prefix) {
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => $current_filename, 'message' => ''];
    }

    if ($_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'message' => 'Erreur lors de l\'upload de l\'image'];
    }

    $upload_result = upload_section4_image_field($_FILES[$field_name], $prefix);
    if (!$upload_result['success']) {
        return $upload_result;
    }

    if (!empty($current_filename) && $current_filename !== $upload_result['filename']) {
        delete_section4_image($current_filename);
    }

    return $upload_result;
}

/**
 * Traite le formulaire de modification de la section4
 * @return array
 */
function process_update_section4() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['success' => false, 'message' => 'Méthode non autorisée'];
    }

    $current_config = get_section4_config();

    $titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';
    $texte = isset($_POST['texte']) ? trim($_POST['texte']) : '';
    $bouton = isset($_POST['bouton']) ? trim($_POST['bouton']) : '';
    $lien = isset($_POST['lien']) ? trim($_POST['lien']) : '';
    $bg_color = isset($_POST['bg_color']) ? trim($_POST['bg_color']) : '';
    $style_bouton = isset($_POST['style_bouton']) && $_POST['style_bouton'] === 'outline' ? 'outline' : 'solid';

    $titre2 = isset($_POST['titre2']) ? trim($_POST['titre2']) : '';
    $texte2 = isset($_POST['texte2']) ? trim($_POST['texte2']) : '';
    $bouton2 = isset($_POST['bouton2']) ? trim($_POST['bouton2']) : '';
    $lien2 = isset($_POST['lien2']) ? trim($_POST['lien2']) : '';
    $bg_color2 = isset($_POST['bg_color2']) ? trim($_POST['bg_color2']) : '';
    $style_bouton2 = isset($_POST['style_bouton2']) && $_POST['style_bouton2'] === 'outline' ? 'outline' : 'solid';
    $actif2 = isset($_POST['actif2']) && $_POST['actif2'] === 'inactif' ? 'inactif' : 'actif';
    $statut = isset($_POST['statut']) && $_POST['statut'] === 'inactif' ? 'inactif' : 'actif';

    $image_result = process_section4_image_upload('image_fond', $current_config['image_fond'] ?? null, 'section4_b1_');
    if (!$image_result['success']) {
        return ['success' => false, 'message' => $image_result['message']];
    }

    $image2_result = process_section4_image_upload('image2', $current_config['image2'] ?? null, 'section4_b2_');
    if (!$image2_result['success']) {
        return ['success' => false, 'message' => $image2_result['message']];
    }

    $data = [
        'titre' => $titre,
        'texte' => $texte,
        'image_fond' => $image_result['filename'],
        'bouton' => $bouton,
        'lien' => $lien,
        'bg_color' => $bg_color,
        'style_bouton' => $style_bouton,
        'titre2' => $titre2,
        'texte2' => $texte2,
        'image2' => $image2_result['filename'],
        'bouton2' => $bouton2,
        'lien2' => $lien2,
        'bg_color2' => $bg_color2,
        'style_bouton2' => $style_bouton2,
        'actif2' => $actif2,
        'statut' => $statut
    ];

    $result = update_section4_config($data);
    if ($result['success']) {
        return ['success' => true, 'message' => 'Configuration de la section4 mise à jour avec succès'];
    }

    $msg = !empty($result['message']) ? $result['message'] : 'Erreur lors de la mise à jour de la configuration';
    return ['success' => false, 'message' => $msg];
}

?>
