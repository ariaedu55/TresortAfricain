<?php
/**
 * Images des catégories — résolution et génération de vignettes
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/image_optimizer.php';

if (!function_exists('categorie_upload_dir')) {
    function categorie_upload_dir() {
        $dir = __DIR__ . '/../upload/categories';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('categorie_image_file_exists')) {
    function categorie_image_file_exists($relative_path) {
        if (!is_string($relative_path) || trim($relative_path) === '') {
            return false;
        }
        $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
        if (strpos($relative_path, 'upload/') === 0) {
            $relative_path = substr($relative_path, 7);
        }
        return file_exists(__DIR__ . '/../upload/' . $relative_path);
    }
}

if (!function_exists('categorie_get_product_images_pool')) {
    /**
     * Liste des images produits disponibles pour illustrer les catégories
     * @param PDO|null $db
     * @return array
     */
    function categorie_get_product_images_pool($db = null) {
        if ($db === null) {
            global $db;
        }

        $pool = [];
        try {
            $stmt = $db->query("
                SELECT DISTINCT image_principale
                FROM produits
                WHERE statut = 'actif'
                  AND image_principale IS NOT NULL
                  AND TRIM(image_principale) != ''
                ORDER BY image_principale ASC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $path = trim($row['image_principale']);
                if ($path !== '' && categorie_image_file_exists($path)) {
                    $pool[] = $path;
                }
            }
        } catch (PDOException $e) {
            return [];
        }

        return $pool;
    }
}

if (!function_exists('categorie_get_first_product_image')) {
    /**
     * Image du produit le plus récent d'une catégorie
     * @param int $categorie_id
     * @param PDO|null $db
     * @return string|null
     */
    function categorie_get_first_product_image($categorie_id, $db = null) {
        if ($db === null) {
            global $db;
        }

        $categorie_id = (int) $categorie_id;
        if ($categorie_id <= 0) {
            return null;
        }

        try {
            $stmt = $db->prepare("
                SELECT image_principale
                FROM produits
                WHERE categorie_id = :categorie_id
                  AND statut = 'actif'
                  AND image_principale IS NOT NULL
                  AND TRIM(image_principale) != ''
                ORDER BY date_creation DESC, id DESC
                LIMIT 1
            ");
            $stmt->execute(['categorie_id' => $categorie_id]);
            $path = trim((string) $stmt->fetchColumn());
            if ($path !== '' && categorie_image_file_exists($path)) {
                return $path;
            }
        } catch (PDOException $e) {
            return null;
        }

        return null;
    }
}

if (!function_exists('categorie_pick_pool_image')) {
    /**
     * Choisit une image produit du pool selon l'ID catégorie
     * @param int $categorie_id
     * @param array $pool
     * @return string|null
     */
    function categorie_pick_pool_image($categorie_id, array $pool) {
        if (empty($pool)) {
            return null;
        }
        $index = abs((int) $categorie_id) % count($pool);
        return $pool[$index];
    }
}

if (!function_exists('categorie_generate_placeholder')) {
    /**
     * Génère une vignette locale pour une catégorie sans image
     * @param int $categorie_id
     * @param string $nom
     * @return string|null Chemin relatif upload/categories/...
     */
    function categorie_generate_placeholder($categorie_id, $nom) {
        if (!extension_loaded('gd')) {
            return null;
        }

        $categorie_id = (int) $categorie_id;
        $relative = 'categories/categorie_' . $categorie_id . '.webp';
        $absolute = categorie_upload_dir() . '/categorie_' . $categorie_id . '.webp';

        if (file_exists($absolute)) {
            return $relative;
        }

        $size = 400;
        $image = imagecreatetruecolor($size, $size);
        if (!$image) {
            return null;
        }

        $bg = imagecolorallocate($image, 250, 247, 242);
        $circle = imagecolorallocate($image, 238, 246, 240);
        $accent = imagecolorallocate($image, 142, 143, 71);
        $text = imagecolorallocate($image, 80, 40, 22);

        imagefilledrectangle($image, 0, 0, $size, $size, $bg);
        imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), 320, 320, $circle);
        imagefilledellipse($image, (int) ($size / 2), (int) ($size / 2), 300, 300, $accent);

        $initial = strtoupper(mb_substr(trim($nom), 0, 1, 'UTF-8'));
        if ($initial === '') {
            $initial = '?';
        }

        $font = 5;
        $text_width = imagefontwidth($font) * strlen($initial);
        $text_height = imagefontheight($font);
        $x = (int) (($size - $text_width) / 2);
        $y = (int) (($size - $text_height) / 2);
        imagestring($image, $font, $x, $y, $initial, $text);

        $saved = image_optimizer_webp_supported()
            ? image_optimizer_save_webp($image, $absolute, 82)
            : false;

        if (!$saved) {
            $absolute = categorie_upload_dir() . '/categorie_' . $categorie_id . '.png';
            $relative = 'categories/categorie_' . $categorie_id . '.png';
            $saved = imagepng($image, $absolute);
        }

        imagedestroy($image);

        return $saved ? $relative : null;
    }
}

if (!function_exists('categorie_create_thumbnail_from_source')) {
    /**
     * Copie/redimensionne une image source vers upload/categories/
     * @param int $categorie_id
     * @param string $source_relative
     * @return string|null
     */
    function categorie_create_thumbnail_from_source($categorie_id, $source_relative) {
        if (!categorie_image_file_exists($source_relative)) {
            return null;
        }

        $categorie_id = (int) $categorie_id;
        $source_abs = __DIR__ . '/../upload/' . ltrim(str_replace('\\', '/', $source_relative), '/');
        $relative = 'categories/categorie_' . $categorie_id . '.webp';
        $dest_abs = categorie_upload_dir() . '/categorie_' . $categorie_id . '.webp';

        if (file_exists($dest_abs)) {
            return $relative;
        }

        if (!extension_loaded('gd')) {
            return ltrim(str_replace('\\', '/', $source_relative), '/');
        }

        $image = image_optimizer_load($source_abs);
        if (!$image) {
            return ltrim(str_replace('\\', '/', $source_relative), '/');
        }

        $image = image_optimizer_resize($image, 400, 400);
        $saved = image_optimizer_webp_supported()
            ? image_optimizer_save_webp($image, $dest_abs, 82)
            : false;

        if (!$saved) {
            $dest_abs = categorie_upload_dir() . '/categorie_' . $categorie_id . '.png';
            $relative = 'categories/categorie_' . $categorie_id . '.png';
            $saved = imagepng($image, $dest_abs);
        }

        imagedestroy($image);

        return $saved ? $relative : ltrim(str_replace('\\', '/', $source_relative), '/');
    }
}

if (!function_exists('categorie_resolve_image')) {
    /**
     * Retourne le chemin relatif d'image à afficher pour une catégorie
     * @param array $categorie
     * @return string
     */
    function categorie_resolve_image(array $categorie) {
        if (!empty($categorie['image']) && categorie_image_file_exists($categorie['image'])) {
            return ltrim(str_replace('\\', '/', $categorie['image']), '/');
        }

        if (!empty($categorie['image_produit']) && categorie_image_file_exists($categorie['image_produit'])) {
            return ltrim(str_replace('\\', '/', $categorie['image_produit']), '/');
        }

        if (!empty($categorie['image_affichee']) && categorie_image_file_exists($categorie['image_affichee'])) {
            return ltrim(str_replace('\\', '/', $categorie['image_affichee']), '/');
        }

        return '';
    }
}

if (!function_exists('categorie_assign_missing_images')) {
    /**
     * Assigne des images aux catégories sans vignette
     * @param PDO|null $db
     * @return array
     */
    function categorie_assign_missing_images($db = null) {
        if ($db === null) {
            global $db;
        }

        $stats = ['updated' => 0, 'from_product' => 0, 'from_pool' => 0, 'generated' => 0, 'skipped' => 0];
        $pool = categorie_get_product_images_pool($db);

        try {
            $stmt = $db->query('SELECT id, nom, image FROM categories ORDER BY id ASC');
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return $stats;
        }

        $update = $db->prepare('UPDATE categories SET image = :image WHERE id = :id');

        foreach ($categories as $categorie) {
            $id = (int) $categorie['id'];
            $current = trim((string) ($categorie['image'] ?? ''));

            if ($current !== '' && categorie_image_file_exists($current)) {
                $stats['skipped']++;
                continue;
            }

            $image_path = null;

            $product_image = categorie_get_first_product_image($id, $db);
            if ($product_image) {
                $image_path = categorie_create_thumbnail_from_source($id, $product_image);
                $stats['from_product']++;
            } elseif (!empty($pool)) {
                $picked = categorie_pick_pool_image($id, $pool);
                $image_path = categorie_create_thumbnail_from_source($id, $picked);
                $stats['from_pool']++;
            } else {
                $image_path = categorie_generate_placeholder($id, $categorie['nom'] ?? '');
                $stats['generated']++;
            }

            if (!$image_path) {
                continue;
            }

            if ($update->execute(['image' => $image_path, 'id' => $id])) {
                $stats['updated']++;
            }
        }

        return $stats;
    }
}

?>
