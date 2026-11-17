<?php
/**
 * Modèle pour la gestion de la configuration de la section4
 * Programmation procédurale uniquement
 */

require_once __DIR__ . '/../conn/conn.php';

/**
 * Valeurs par défaut de la configuration section4
 * @return array
 */
function section4_default_config() {
    return [
        'id' => 0,
        'titre' => 'Petits prix sur nos bestsellers',
        'texte' => 'PROMOTION',
        'image_fond' => 'market.png',
        'bouton' => 'Voir les promos',
        'lien' => 'promo.php',
        'bg_color' => '#eef6f0',
        'style_bouton' => 'solid',
        'titre2' => 'Découvrez nos nouveautés',
        'texte2' => 'NOUVEAUTÉS',
        'image2' => null,
        'bouton2' => 'Explorer',
        'lien2' => 'nouveautes.php',
        'bg_color2' => '#faf7f2',
        'style_bouton2' => 'outline',
        'actif2' => 'actif',
        'statut' => 'actif',
        'date_modification' => date('Y-m-d H:i:s')
    ];
}

/**
 * Ajoute les colonnes manquantes pour les deux bannières
 * @return void
 */
function section4_ensure_schema() {
    global $db;

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $columns = [
        'bouton' => "VARCHAR(100) NULL DEFAULT 'Voir les promos'",
        'lien' => "VARCHAR(255) NULL DEFAULT 'promo.php'",
        'bg_color' => "VARCHAR(20) NULL DEFAULT '#eef6f0'",
        'style_bouton' => "VARCHAR(20) NULL DEFAULT 'solid'",
        'titre2' => "VARCHAR(255) NULL DEFAULT 'Découvrez nos nouveautés'",
        'texte2' => "VARCHAR(255) NULL DEFAULT 'NOUVEAUTÉS'",
        'image2' => "VARCHAR(255) NULL DEFAULT NULL",
        'bouton2' => "VARCHAR(100) NULL DEFAULT 'Explorer'",
        'lien2' => "VARCHAR(255) NULL DEFAULT 'nouveautes.php'",
        'bg_color2' => "VARCHAR(20) NULL DEFAULT '#faf7f2'",
        'style_bouton2' => "VARCHAR(20) NULL DEFAULT 'outline'",
        'actif2' => "VARCHAR(20) NULL DEFAULT 'actif'",
        'statut' => "VARCHAR(20) NULL DEFAULT 'actif'"
    ];

    try {
        foreach ($columns as $name => $definition) {
            if (!section4_has_column($name)) {
                $db->exec("ALTER TABLE section4_config ADD COLUMN `$name` $definition");
                section4_reset_column_cache();
            }
        }
    } catch (PDOException $e) {
        // La table peut ne pas exister en dev local
    }
}

/**
 * Réinitialise le cache des colonnes
 * @return void
 */
function section4_reset_column_cache() {
    global $db;
    $GLOBALS['section4_columns_cache'] = null;
}

/**
 * Récupère la configuration de la section4
 * @return array
 */
function get_section4_config() {
    global $db;

    section4_ensure_schema();

    try {
        $stmt = $db->prepare("SELECT * FROM section4_config ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            return section4_default_config();
        }

        return array_merge(section4_default_config(), $config);
    } catch (PDOException $e) {
        return section4_default_config();
    }
}

/**
 * Vérifie si une colonne existe dans la table section4_config
 * @param string $column_name Nom de la colonne
 * @return bool
 */
function section4_has_column($column_name) {
    global $db;

    if (!isset($GLOBALS['section4_columns_cache'])) {
        $GLOBALS['section4_columns_cache'] = null;
    }

    if ($GLOBALS['section4_columns_cache'] === null) {
        try {
            $stmt = $db->query("SHOW COLUMNS FROM section4_config");
            $GLOBALS['section4_columns_cache'] = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $GLOBALS['section4_columns_cache'][] = $row['Field'];
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    return in_array($column_name, $GLOBALS['section4_columns_cache'], true);
}

/**
 * Met à jour la configuration de la section4
 * @param array $data Les données de configuration
 * @return array ['success' => bool, 'message' => string]
 */
function update_section4_config($data) {
    global $db;

    section4_ensure_schema();

    try {
        $existing = get_section4_config();
        $defaults = section4_default_config();

        $fields = [
            'titre' => isset($data['titre']) ? trim($data['titre']) : $defaults['titre'],
            'texte' => isset($data['texte']) ? trim($data['texte']) : $defaults['texte'],
            'image_fond' => isset($data['image_fond']) ? $data['image_fond'] : $existing['image_fond'],
            'bouton' => isset($data['bouton']) ? trim($data['bouton']) : $defaults['bouton'],
            'lien' => isset($data['lien']) ? trim($data['lien']) : $defaults['lien'],
            'bg_color' => isset($data['bg_color']) ? trim($data['bg_color']) : $defaults['bg_color'],
            'style_bouton' => isset($data['style_bouton']) && $data['style_bouton'] === 'outline' ? 'outline' : 'solid',
            'titre2' => isset($data['titre2']) ? trim($data['titre2']) : $defaults['titre2'],
            'texte2' => isset($data['texte2']) ? trim($data['texte2']) : $defaults['texte2'],
            'image2' => isset($data['image2']) ? $data['image2'] : $existing['image2'],
            'bouton2' => isset($data['bouton2']) ? trim($data['bouton2']) : $defaults['bouton2'],
            'lien2' => isset($data['lien2']) ? trim($data['lien2']) : $defaults['lien2'],
            'bg_color2' => isset($data['bg_color2']) ? trim($data['bg_color2']) : $defaults['bg_color2'],
            'style_bouton2' => isset($data['style_bouton2']) && $data['style_bouton2'] === 'outline' ? 'outline' : 'solid',
            'actif2' => isset($data['actif2']) && $data['actif2'] === 'inactif' ? 'inactif' : 'actif',
            'statut' => isset($data['statut']) && $data['statut'] === 'inactif' ? 'inactif' : 'actif'
        ];

        if ($existing && isset($existing['id']) && (int) $existing['id'] > 0) {
            $set_parts = [];
            $params = ['id' => $existing['id']];

            foreach ($fields as $column => $value) {
                if (section4_has_column($column)) {
                    $set_parts[] = "$column = :$column";
                    $params[$column] = $value;
                }
            }

            $set_parts[] = 'date_modification = NOW()';
            $sql = "UPDATE section4_config SET " . implode(', ', $set_parts) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $ok = $stmt->execute($params);
            return $ok ? ['success' => true, 'message' => ''] : ['success' => false, 'message' => 'Échec de l\'exécution'];
        }

        $cols = [];
        $placeholders = [];
        $params = [];

        foreach ($fields as $column => $value) {
            if (section4_has_column($column)) {
                $cols[] = $column;
                $placeholders[] = ":$column";
                $params[$column] = $value;
            }
        }

        $cols[] = 'date_modification';
        $placeholders[] = 'NOW()';
        $sql = "INSERT INTO section4_config (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $ok = $stmt->execute($params);
        return $ok ? ['success' => true, 'message' => ''] : ['success' => false, 'message' => 'Échec de l\'exécution'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Supprime une image de la section4
 * @param string $image_name Le nom de l'image à supprimer
 * @return bool
 */
function delete_section4_image($image_name) {
    if (empty($image_name)) {
        return false;
    }

    $upload_dir = __DIR__ . '/../upload/section4/';
    $image_path = $upload_dir . $image_name;

    if (file_exists($image_path)) {
        return unlink($image_path);
    }

    return false;
}

?>
