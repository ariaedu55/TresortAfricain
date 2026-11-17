<?php

/**
 * Configuration de connexion à la base de données
 * Copiez ce fichier en conn.php et modifiez les valeurs selon votre environnement
 */

// Charger l'autoload Composer (PHPMailer, Firebase, etc.)
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Paramètres de connexion
$db_host = "127.0.0.1";
$db_name = "tresor_afri";
$db_user = "root";
$db_pass = "";

try {
  require_once __DIR__ . '/../includes/text_encoding.php';
  init_app_encoding();

  $db = new PDO(
    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
    $db_user,
    $db_pass
  );

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");


} catch (PDOException $e) {
  // Gestion des erreurs
}

?>
