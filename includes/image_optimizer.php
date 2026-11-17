<?php
/**
 * Optimisation images : conversion WebP, redimensionnement, affichage
 */

require_once __DIR__ . '/text_encoding.php';

if (!function_exists('image_optimizer_webp_supported')) {
    function image_optimizer_webp_supported() {
        return extension_loaded('gd') && function_exists('imagewebp');
    }
}

if (!function_exists('image_optimizer_upload_root')) {
    function image_optimizer_upload_root() {
        return realpath(__DIR__ . '/../upload') ?: (__DIR__ . '/../upload');
    }
}

if (!function_exists('image_optimizer_allowed_mimes')) {
    function image_optimizer_allowed_mimes() {
        return ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    }
}

if (!function_exists('image_optimizer_is_allowed_upload')) {
    function image_optimizer_is_allowed_upload($tmp_path, $declared_mime = '') {
        if (!is_string($tmp_path) || $tmp_path === '' || !file_exists($tmp_path)) {
            return false;
        }

        $mime = $declared_mime;
        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = mime_content_type($tmp_path);
        }
        if ($mime === '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $tmp_path);
                finfo_close($finfo);
            }
        }

        return in_array($mime, image_optimizer_allowed_mimes(), true);
    }
}

if (!function_exists('image_optimizer_load')) {
    function image_optimizer_load($path) {
        $info = @getimagesize($path);
        if (!$info || empty($info[2])) {
            return false;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($path);
                if ($image) {
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                return $image;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
            default:
                return false;
        }
    }
}

if (!function_exists('image_optimizer_resize')) {
    function image_optimizer_resize($image, $max_width, $max_height) {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $max_width && $height <= $max_height) {
            return $image;
        }

        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = max(1, (int) round($width * $ratio));
        $new_height = max(1, (int) round($height * $ratio));

        $resized = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $new_width, $new_height, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}

if (!function_exists('image_optimizer_save_webp')) {
    function image_optimizer_save_webp($image, $dest_path, $quality = 82) {
        if (!image_optimizer_webp_supported()) {
            return false;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return imagewebp($image, $dest_path, $quality);
    }
}

if (!function_exists('image_optimizer_detect_extension')) {
    function image_optimizer_detect_extension($path) {
        $info = @getimagesize($path);
        if (!$info || empty($info[2])) {
            return 'jpg';
        }

        switch ($info[2]) {
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            default:
                return 'jpg';
        }
    }
}

if (!function_exists('image_optimizer_process_uploaded_file')) {
    function image_optimizer_process_uploaded_file($tmp_path, $dest_dir_abs, $options = []) {
        $options = array_merge([
            'filename_prefix' => 'img_',
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 82,
            'declared_mime' => '',
        ], $options);

        if (!image_optimizer_is_allowed_upload($tmp_path, $options['declared_mime'])) {
            return ['success' => false, 'filename' => null, 'message' => 'Type de fichier non autorisé'];
        }

        if (!is_dir($dest_dir_abs) && !mkdir($dest_dir_abs, 0755, true) && !is_dir($dest_dir_abs)) {
            return ['success' => false, 'filename' => null, 'message' => 'Impossible de créer le dossier de destination'];
        }

        $basename = $options['filename_prefix'] . str_replace('.', '', uniqid('', true));

        if (image_optimizer_webp_supported()) {
            $image = image_optimizer_load($tmp_path);
            if ($image) {
                $image = image_optimizer_resize($image, (int) $options['max_width'], (int) $options['max_height']);
                $filename = $basename . '.webp';
                $dest_path = rtrim($dest_dir_abs, '/\\') . DIRECTORY_SEPARATOR . $filename;

                if (image_optimizer_save_webp($image, $dest_path, (int) $options['quality'])) {
                    imagedestroy($image);
                    return ['success' => true, 'filename' => $filename, 'message' => ''];
                }

                imagedestroy($image);
            }
        }

        $extension = image_optimizer_detect_extension($tmp_path);
        $filename = $basename . '.' . $extension;
        $dest_path = rtrim($dest_dir_abs, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (@copy($tmp_path, $dest_path)) {
            return ['success' => true, 'filename' => $filename, 'message' => ''];
        }

        return ['success' => false, 'filename' => null, 'message' => 'Erreur lors de l\'enregistrement de l\'image'];
    }
}

if (!function_exists('image_optimizer_absolute_from_relative')) {
    function image_optimizer_absolute_from_relative($relative_path) {
        $relative_path = ltrim(str_replace('\\', '/', (string) $relative_path), '/');
        return rtrim(image_optimizer_upload_root(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    }
}

if (!function_exists('image_optimizer_is_convertible_path')) {
    function image_optimizer_is_convertible_path($path) {
        return is_string($path) && preg_match('/\.(jpe?g|png|gif)$/i', $path);
    }
}

if (!function_exists('image_optimizer_ensure_webp_relative')) {
    function image_optimizer_ensure_webp_relative($relative_path, $options = []) {
        static $lazy_conversions = 0;
        $lazy_limit = 2;

        if (!image_optimizer_webp_supported() || !is_string($relative_path) || trim($relative_path) === '') {
            return null;
        }

        $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
        $absolute = image_optimizer_absolute_from_relative($relative_path);

        if (preg_match('/\.webp$/i', $relative_path)) {
            return file_exists($absolute) ? $relative_path : null;
        }

        $webp_relative = image_optimizer_replace_extension($relative_path, 'webp');
        $webp_absolute = image_optimizer_absolute_from_relative($webp_relative);

        if (file_exists($webp_absolute)) {
            return $webp_relative;
        }

        if (!image_optimizer_is_convertible_path($relative_path) || !file_exists($absolute)) {
            return null;
        }

        if ($lazy_conversions >= $lazy_limit) {
            return null;
        }

        $result = image_optimizer_convert_file($absolute, array_merge([
            'max_width' => 1920,
            'max_height' => 1920,
            'quality' => 82,
            'delete_original' => true,
        ], $options));

        if (empty($result['success'])) {
            return null;
        }

        $lazy_conversions++;
        image_optimizer_sync_db_path($relative_path, $webp_relative);

        return $webp_relative;
    }
}

if (!function_exists('image_optimizer_find_pending_files')) {
    function image_optimizer_find_pending_files($limit = 5) {
        $root = image_optimizer_upload_root();
        if (!is_dir($root)) {
            return [];
        }

        $pending = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file_info) {
            if (!$file_info->isFile()) {
                continue;
            }

            $absolute = $file_info->getPathname();
            if (!image_optimizer_is_convertible_path($absolute)) {
                continue;
            }

            $webp_absolute = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $absolute);
            if (file_exists($webp_absolute)) {
                continue;
            }

            $pending[] = $absolute;
            if (count($pending) >= $limit) {
                break;
            }
        }

        return $pending;
    }
}

if (!function_exists('image_optimizer_sync_db_path')) {
    function image_optimizer_sync_db_path($old_relative, $new_relative) {
        global $db;

        if (!isset($db) || !($db instanceof PDO) || $old_relative === $new_relative) {
            return;
        }

        $updates = [
            ['produits', 'image_principale', 'id'],
            ['categories', 'image', 'id'],
            ['produits_variantes', 'image', 'id'],
            ['slider', 'image', 'id'],
            ['section4_config', 'image_fond', 'id'],
            ['trending_config', 'image', 'id'],
            ['commandes_personnalisees', 'image_reference', 'id'],
        ];

        foreach ($updates as $item) {
            list($table, $column, $pk) = $item;

            try {
                $db->query("SELECT 1 FROM `$table` LIMIT 1");
            } catch (PDOException $e) {
                continue;
            }

            if ($column === 'images') {
                continue;
            }

            $old_values = [$old_relative];
            $new_values = [$new_relative];

            if (in_array($table, ['slider', 'section4_config', 'trending_config'], true)) {
                $old_values[] = basename($old_relative);
                $new_values[] = basename($new_relative);
            }

            foreach ($old_values as $index => $old_value) {
                $new_value = $new_values[$index] ?? $new_relative;

                try {
                    $stmt = $db->prepare("UPDATE `$table` SET `$column` = :new_value WHERE `$column` = :old_value");
                    $stmt->execute([
                        'new_value' => $new_value,
                        'old_value' => $old_value,
                    ]);
                } catch (PDOException $e) {
                    continue;
                }
            }
        }

        try {
            $stmt = $db->query("SELECT id, images FROM produits WHERE images IS NOT NULL AND images != ''");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $row) {
                $decoded = json_decode($row['images'], true);
                if (!is_array($decoded)) {
                    continue;
                }
                $changed = false;
                foreach ($decoded as $index => $item) {
                    if ($item === $old_relative) {
                        $decoded[$index] = $new_relative;
                        $changed = true;
                    }
                }
                if ($changed) {
                    $update = $db->prepare('UPDATE produits SET images = :images WHERE id = :id');
                    $update->execute([
                        'images' => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                        'id' => $row['id'],
                    ]);
                }
            }
        } catch (PDOException $e) {
            return;
        }
    }
}

if (!function_exists('image_optimizer_auto_migrate_tick')) {
    function image_optimizer_auto_migrate_tick() {
        static $ran = false;

        if ($ran || PHP_SAPI === 'cli' || !image_optimizer_webp_supported()) {
            return;
        }
        $ran = true;

        $pending = image_optimizer_find_pending_files(3);
        foreach ($pending as $absolute_path) {
            $relative = ltrim(str_replace('\\', '/', substr($absolute_path, strlen(image_optimizer_upload_root()))), '/');
            $result = image_optimizer_convert_file($absolute_path, [
                'max_width' => 1920,
                'max_height' => 1920,
                'quality' => 82,
                'delete_original' => true,
            ]);

            if (!empty($result['success'])) {
                $webp_relative = image_optimizer_replace_extension($relative, 'webp');
                image_optimizer_sync_db_path($relative, $webp_relative);
            }
        }
    }
}

if (!function_exists('image_optimizer_convert_file')) {
    function image_optimizer_convert_file($absolute_path, $options = []) {
        $options = array_merge([
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 82,
            'delete_original' => true,
        ], $options);

        if (!file_exists($absolute_path) || !image_optimizer_webp_supported()) {
            return ['success' => false, 'webp_path' => null, 'message' => 'Conversion impossible'];
        }

        if (preg_match('/\.webp$/i', $absolute_path)) {
            return ['success' => true, 'webp_path' => $absolute_path, 'message' => 'Déjà en WebP'];
        }

        $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $absolute_path);
        if ($webp_path === $absolute_path) {
            return ['success' => false, 'webp_path' => null, 'message' => 'Extension non supportée'];
        }

        $image = image_optimizer_load($absolute_path);
        if (!$image) {
            return ['success' => false, 'webp_path' => null, 'message' => 'Image illisible'];
        }

        $image = image_optimizer_resize($image, (int) $options['max_width'], (int) $options['max_height']);
        $saved = image_optimizer_save_webp($image, $webp_path, (int) $options['quality']);
        imagedestroy($image);

        if (!$saved) {
            return ['success' => false, 'webp_path' => null, 'message' => 'Échec de la conversion WebP'];
        }

        if (!empty($options['delete_original']) && file_exists($absolute_path)) {
            @unlink($absolute_path);
        }

        return ['success' => true, 'webp_path' => $webp_path, 'message' => ''];
    }
}

if (!function_exists('image_optimizer_replace_extension')) {
    function image_optimizer_replace_extension($path, $new_ext = 'webp') {
        if (!is_string($path) || $path === '') {
            return $path;
        }

        return preg_replace('/\.(jpe?g|png|gif|webp|avif)$/i', '.' . $new_ext, $path);
    }
}

if (!function_exists('image_optimizer_delete_file')) {
    function image_optimizer_delete_file($absolute_path) {
        if (!is_string($absolute_path) || $absolute_path === '' || !file_exists($absolute_path)) {
            return;
        }

        @unlink($absolute_path);

        $webp = image_optimizer_replace_extension($absolute_path, 'webp');
        if ($webp !== $absolute_path && file_exists($webp)) {
            @unlink($webp);
        }

        foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
            $alt = image_optimizer_replace_extension($absolute_path, $ext);
            if ($alt !== $absolute_path && file_exists($alt)) {
                @unlink($alt);
            }
        }
    }
}

if (!function_exists('image_optimizer_delete_upload')) {
    function image_optimizer_delete_upload($relative_path) {
        if (!is_string($relative_path) || trim($relative_path) === '') {
            return;
        }

        $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
        $absolute = rtrim(image_optimizer_upload_root(), '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
        image_optimizer_delete_file($absolute);
    }
}

if (!function_exists('image_upload_webp_relative')) {
    function image_upload_webp_relative($relative_path) {
        if (!is_string($relative_path) || trim($relative_path) === '') {
            return null;
        }

        $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
        $root = rtrim(image_optimizer_upload_root(), '/\\') . DIRECTORY_SEPARATOR;

        if (preg_match('/\.webp$/i', $relative_path) && file_exists($root . str_replace('/', DIRECTORY_SEPARATOR, $relative_path))) {
            return $relative_path;
        }

        $webp_relative = image_optimizer_replace_extension($relative_path, 'webp');
        if (file_exists($root . str_replace('/', DIRECTORY_SEPARATOR, $webp_relative))) {
            return $webp_relative;
        }

        return image_optimizer_ensure_webp_relative($relative_path);
    }
}

if (!function_exists('image_upload_fallback_url')) {
    function image_upload_fallback_url($relative_path, $static_fallback = '/image/produit1.jpg') {
        if (!is_string($relative_path) || trim($relative_path) === '') {
            return $static_fallback;
        }

        $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
        $root = rtrim(image_optimizer_upload_root(), '/\\') . DIRECTORY_SEPARATOR;
        $absolute = $root . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);

        if (file_exists($absolute)) {
            return '/upload/' . $relative_path;
        }

        $webp_relative = image_upload_webp_relative($relative_path);
        if ($webp_relative) {
            return '/upload/' . $webp_relative;
        }

        return $static_fallback;
    }
}

if (!function_exists('image_upload_webp_url')) {
    function image_upload_webp_url($relative_path) {
        $webp_relative = image_upload_webp_relative($relative_path);
        return $webp_relative ? '/upload/' . $webp_relative : null;
    }
}

if (!function_exists('image_upload_url')) {
    function image_upload_url($relative_path, $static_fallback = '/image/produit1.jpg') {
        $webp_url = image_upload_webp_url($relative_path);
        return $webp_url ?: image_upload_fallback_url($relative_path, $static_fallback);
    }
}

if (!function_exists('render_upload_img')) {
    function render_upload_img($relative_path, $alt = '', $attrs = [], $static_fallback = '/image/produit1.jpg') {
        $defaults = [
            'loading' => 'lazy',
            'decoding' => 'async',
        ];
        $attrs = array_merge($defaults, $attrs);

        $fallback_url = image_upload_fallback_url($relative_path, $static_fallback);
        $webp_url = image_upload_webp_url($relative_path);
        $alt_text = htmlspecialchars(normalize_utf8((string) $alt), ENT_QUOTES, 'UTF-8');

        $attr_html = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false || $key === 'src') {
                continue;
            }
            $attr_html .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        if ($webp_url && $webp_url !== $fallback_url) {
            echo '<picture>';
            echo '<source srcset="' . htmlspecialchars($webp_url, ENT_QUOTES, 'UTF-8') . '" type="image/webp">';
            echo '<img src="' . htmlspecialchars($fallback_url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt_text . '"' . $attr_html . ' onerror="this.onerror=null;this.src=\'' . htmlspecialchars($static_fallback, ENT_QUOTES, 'UTF-8') . '\';">';
            echo '</picture>';
            return;
        }

        echo '<img src="' . htmlspecialchars($fallback_url, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt_text . '"' . $attr_html . ' onerror="this.onerror=null;this.src=\'' . htmlspecialchars($static_fallback, ENT_QUOTES, 'UTF-8') . '\';">';
    }
}

if (!function_exists('image_optimizer_update_db_path')) {
    function image_optimizer_update_db_path($path) {
        if (!is_string($path) || $path === '') {
            return $path;
        }

        return image_optimizer_replace_extension($path, 'webp');
    }
}

if (PHP_SAPI !== 'cli') {
    register_shutdown_function(function () {
        image_optimizer_auto_migrate_tick();
    });
}
