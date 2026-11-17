<?php
/**
 * Conversion batch des images existantes en WebP (optionnel, outil manuel).
 * La conversion est désormais automatique à chaque visite du site et à l'affichage.
 * Usage : php migrations/convert_images_webp.php [--keep-original]
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/image_optimizer.php';

if (!isset($db) || !($db instanceof PDO)) {
    fwrite(STDERR, "Connexion base de données indisponible.\n");
    exit(1);
}

$keep_original = in_array('--keep-original', $argv ?? [], true);
$upload_root = image_optimizer_upload_root();
if (!is_dir($upload_root) && !mkdir($upload_root, 0755, true) && !is_dir($upload_root)) {
    fwrite(STDERR, "Dossier upload introuvable.\n");
    exit(1);
}

if (!image_optimizer_webp_supported()) {
    fwrite(STDERR, "L'extension GD avec support WebP est requise.\n");
    exit(1);
}

$extensions = ['jpg', 'jpeg', 'png', 'gif'];
$converted_files = 0;
$updated_db = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($upload_root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file_info) {
    if (!$file_info->isFile()) {
        continue;
    }

    $extension = strtolower($file_info->getExtension());
    if (!in_array($extension, $extensions, true)) {
        continue;
    }

    $absolute_path = $file_info->getPathname();
    $result = image_optimizer_convert_file($absolute_path, [
        'max_width' => 1920,
        'max_height' => 1920,
        'quality' => 82,
        'delete_original' => !$keep_original,
    ]);

    if (empty($result['success'])) {
        continue;
    }

    $converted_files++;
    $relative = ltrim(str_replace('\\', '/', substr($absolute_path, strlen($upload_root))), '/');
    echo "[WEBP] {$relative}\n";
}

function convert_images_webp_update_column(PDO $db, $table, $column, $primary_key = 'id') {
    global $updated_db;

    try {
        $db->query("SELECT 1 FROM `$table` LIMIT 1");
    } catch (PDOException $e) {
        return;
    }

    try {
        $db->query("SELECT `$column` FROM `$table` LIMIT 1");
    } catch (PDOException $e) {
        return;
    }

    $stmt = $db->query("SELECT `$primary_key`, `$column` FROM `$table` WHERE `$column` IS NOT NULL AND `$column` != ''");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $value = $row[$column];
        $new_value = null;

        if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                continue;
            }
            $changed = false;
            foreach ($decoded as $index => $item) {
                if (!is_string($item) || $item === '') {
                    continue;
                }
                $updated = image_optimizer_update_db_path($item);
                if ($updated !== $item) {
                    $decoded[$index] = $updated;
                    $changed = true;
                }
            }
            if ($changed) {
                $new_value = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        } elseif (is_string($value)) {
            $updated = image_optimizer_update_db_path($value);
            if ($updated !== $value) {
                $new_value = $updated;
            }
        }

        if ($new_value === null) {
            continue;
        }

        $update = $db->prepare("UPDATE `$table` SET `$column` = :value WHERE `$primary_key` = :id");
        $update->execute([
            'value' => $new_value,
            'id' => $row[$primary_key],
        ]);
        $updated_db++;
        echo "[DB] {$table}.{$column} #{$row[$primary_key]}\n";
    }
}

convert_images_webp_update_column($db, 'produits', 'image_principale');
convert_images_webp_update_column($db, 'produits', 'images');
convert_images_webp_update_column($db, 'categories', 'image');
convert_images_webp_update_column($db, 'produits_variantes', 'image');
convert_images_webp_update_column($db, 'slider', 'image');
convert_images_webp_update_column($db, 'section4_config', 'image_fond');
convert_images_webp_update_column($db, 'trending_config', 'image');
convert_images_webp_update_column($db, 'commandes_personnalisees', 'image_reference');

echo PHP_EOL . "Terminé : {$converted_files} fichier(s) converti(s), {$updated_db} enregistrement(s) BDD mis à jour." . PHP_EOL;
