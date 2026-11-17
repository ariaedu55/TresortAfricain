-- Script SQL pour créer la table `section4_config`
-- Deux bannières promo côte à côte sur la page d'accueil

CREATE TABLE IF NOT EXISTS `section4_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(255) NOT NULL DEFAULT 'Petits prix sur nos bestsellers',
  `texte` VARCHAR(255) NOT NULL DEFAULT 'PROMOTION',
  `image_fond` VARCHAR(255) NULL DEFAULT NULL,
  `bouton` VARCHAR(100) NULL DEFAULT 'Voir les promos',
  `lien` VARCHAR(255) NULL DEFAULT 'promo.php',
  `bg_color` VARCHAR(20) NULL DEFAULT '#eef6f0',
  `style_bouton` VARCHAR(20) NULL DEFAULT 'solid',
  `titre2` VARCHAR(255) NULL DEFAULT 'Découvrez nos nouveautés',
  `texte2` VARCHAR(255) NULL DEFAULT 'NOUVEAUTÉS',
  `image2` VARCHAR(255) NULL DEFAULT NULL,
  `bouton2` VARCHAR(100) NULL DEFAULT 'Explorer',
  `lien2` VARCHAR(255) NULL DEFAULT 'nouveautes.php',
  `bg_color2` VARCHAR(20) NULL DEFAULT '#faf7f2',
  `style_bouton2` VARCHAR(20) NULL DEFAULT 'outline',
  `actif2` VARCHAR(20) NULL DEFAULT 'actif',
  `statut` VARCHAR(20) NULL DEFAULT 'actif',
  `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `section4_config` (`titre`, `texte`, `image_fond`, `bouton`, `lien`, `bg_color`, `style_bouton`, `titre2`, `texte2`, `bouton2`, `lien2`, `bg_color2`, `style_bouton2`, `actif2`, `statut`)
VALUES (
  'Petits prix sur nos bestsellers',
  'PROMOTION',
  'market.png',
  'Voir les promos',
  'promo.php',
  '#eef6f0',
  'solid',
  'Découvrez nos nouveautés',
  'NOUVEAUTÉS',
  'Explorer',
  'nouveautes.php',
  '#faf7f2',
  'outline',
  'actif',
  'actif'
)
ON DUPLICATE KEY UPDATE `titre` = VALUES(`titre`);
