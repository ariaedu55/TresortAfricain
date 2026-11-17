<?php
/**
 * Réparation automatique de l'encodage UTF-8 en base de données.
 * Usage : php migrations/fix_utf8_data.php
 */

require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/text_encoding.php';

if (!isset($db) || !($db instanceof PDO)) {
    fwrite(STDERR, "Connexion base de données indisponible.\n");
    exit(1);
}

$tables = [
    'categories',
    'categories_generales',
    'produits',
    'produits_variantes',
    'section4_config',
    'slider',
    'videos',
    'trending_config',
    'zones_livraison',
    'users',
    'admin',
    'commandes',
    'commandes_personnalisees',
    'commande_produits',
    'devis',
    'devis_produits',
    'contacts',
    'factures',
    'factures_devis',
    'factures_personnalisees',
    'genres',
];

$total_fixed = 0;
$total_checked = 0;

foreach ($tables as $table) {
    try {
        $db->query("SELECT 1 FROM `$table` LIMIT 1");
    } catch (PDOException $e) {
        continue;
    }

    $columns_stmt = $db->query("SHOW FULL COLUMNS FROM `$table`");
    $columns = $columns_stmt ? $columns_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (empty($columns)) {
        continue;
    }

    $text_columns = [];
    $primary_key = 'id';
    foreach ($columns as $column) {
        $field = $column['Field'];
        $type = strtolower($column['Type']);
        if ($column['Key'] === 'PRI') {
            $primary_key = $field;
        }
        if (preg_match('/char|text|enum|set/', $type)) {
            $text_columns[] = $field;
        }
    }

    if (empty($text_columns)) {
        continue;
    }

    $rows_stmt = $db->query("SELECT * FROM `$table`");
    $rows = $rows_stmt ? $rows_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $total_checked++;
        if (!array_key_exists($primary_key, $row)) {
            continue;
        }

        $updates = [];
        foreach ($text_columns as $column) {
            if (!isset($row[$column]) || !is_string($row[$column]) || $row[$column] === '') {
                continue;
            }

            $fixed = normalize_utf8($row[$column]);
            if ($fixed !== $row[$column]) {
                $updates[$column] = $fixed;
            }
        }

        if (empty($updates)) {
            continue;
        }

        $set_parts = [];
        $params = [$primary_key => $row[$primary_key]];
        foreach ($updates as $column => $value) {
            $set_parts[] = "`$column` = :$column";
            $params[$column] = $value;
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $set_parts) . " WHERE `$primary_key` = :$primary_key";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $total_fixed++;
        echo "[FIX] {$table} #{$row[$primary_key]} : " . implode(', ', array_keys($updates)) . PHP_EOL;
    }
}

echo PHP_EOL . "Terminé : {$total_fixed} enregistrement(s) corrigé(s) sur {$total_checked} vérifié(s)." . PHP_EOL;
