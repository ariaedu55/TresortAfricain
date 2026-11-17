<?php
/**
 * Modèle pour la gestion des produits visités
 * Programmation procédurale uniquement
 */

// Inclusion du fichier de connexion à la BDD
require_once __DIR__ . '/../conn/conn.php';

/**
 * Enregistre une visite d'un produit
 * @param int $user_id L'ID de l'utilisateur
 * @param int $produit_id L'ID du produit
 * @return bool True en cas de succès, False sinon
 */
function add_visite($user_id, $produit_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO produits_visites (user_id, produit_id, date_visite) 
            VALUES (:user_id, :produit_id, NOW())
            ON DUPLICATE KEY UPDATE date_visite = NOW()
        ");
        
        return $stmt->execute([
            'user_id' => $user_id,
            'produit_id' => $produit_id
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère les produits visités par un utilisateur
 * @param int $user_id L'ID de l'utilisateur
 * @param int $limit Nombre maximum de résultats (par défaut 20)
 * @return array|false Tableau des produits visités ou False en cas d'erreur
 */
function get_produits_visites_by_user($user_id, $limit = 20) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT pv.*, p.*, c.nom as categorie_nom
            FROM produits_visites pv
            INNER JOIN produits p ON pv.produit_id = p.id
            LEFT JOIN categories c ON p.categorie_id = c.id
            WHERE pv.user_id = :user_id
            ORDER BY pv.date_visite DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $visites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $visites ? $visites : [];
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Compte le nombre de produits visités par un utilisateur
 * @param int $user_id L'ID de l'utilisateur
 * @return int Le nombre de produits visités
 */
function count_visites_by_user($user_id) {
    global $db;
    
    try {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT produit_id) FROM produits_visites WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Récupère les produits les plus populaires (visites, commandes, ajouts panier)
 * @param int $limit Nombre maximum de produits à retourner
 * @return array Tableau des produits triés par popularité décroissante
 */
function get_produits_plus_visites($limit = 8, $exclude_ids = []) {
    global $db;

    if (file_exists(__DIR__ . '/model_produits.php')) {
        require_once __DIR__ . '/model_produits.php';
    }

    try {
        $params = [];
        $exclude_sql = model_produits_build_exclude_clause($exclude_ids, $params);

        $stmt = $db->prepare("
            SELECT
                p.*,
                c.nom as categorie_nom,
                COALESCE(visites_stats.nb_visites, 0) as nb_visites,
                COALESCE(commande_stats.nb_commandes, 0) as nb_commandes,
                COALESCE(panier_stats.nb_ajouts_panier, 0) as nb_ajouts_panier,
                (
                    COALESCE(visites_stats.nb_visites, 0)
                    + (COALESCE(commande_stats.nb_commandes, 0) * 3)
                    + COALESCE(panier_stats.nb_ajouts_panier, 0)
                ) as score_popularite
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id = c.id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_visites
                FROM produits_visites
                GROUP BY produit_id
            ) visites_stats ON p.id = visites_stats.produit_id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_commandes
                FROM commande_produits
                GROUP BY produit_id
            ) commande_stats ON p.id = commande_stats.produit_id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_ajouts_panier
                FROM panier
                GROUP BY produit_id
            ) panier_stats ON p.id = panier_stats.produit_id
            WHERE p.statut = 'actif'
            $exclude_sql
            ORDER BY score_popularite DESC, nb_visites DESC, p.date_creation DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($produits) && function_exists('get_all_produits_paginated')) {
            $produits = get_all_produits_paginated(0, $limit, $exclude_ids);
        }

        if (function_exists('model_produits_normalize')) {
            return model_produits_normalize($produits ? $produits : []);
        }

        return $produits ? $produits : [];
    } catch (PDOException $e) {
        if (function_exists('get_all_produits_paginated')) {
            $produits = get_all_produits_paginated(0, $limit, $exclude_ids);
            if (function_exists('model_produits_normalize')) {
                return model_produits_normalize($produits);
            }
            return $produits;
        }
        return [];
    }
}

/**
 * Produits les plus populaires d'une catégorie
 * @param int $categorie_id
 * @param int $limit
 * @param array $exclude_ids
 * @return array
 */
function get_produits_populaires_by_categorie($categorie_id, $limit = 4, $exclude_ids = []) {
    global $db;

    $categorie_id = (int) $categorie_id;
    $limit = max(1, (int) $limit);

    if ($categorie_id <= 0) {
        return [];
    }

    if (file_exists(__DIR__ . '/model_produits.php')) {
        require_once __DIR__ . '/model_produits.php';
    }

    try {
        $params = ['categorie_id' => $categorie_id];
        $exclude_sql = model_produits_build_exclude_clause($exclude_ids, $params);

        $stmt = $db->prepare("
            SELECT
                p.*,
                c.nom as categorie_nom,
                COALESCE(visites_stats.nb_visites, 0) as nb_visites,
                COALESCE(commande_stats.nb_commandes, 0) as nb_commandes,
                COALESCE(panier_stats.nb_ajouts_panier, 0) as nb_ajouts_panier,
                (
                    COALESCE(visites_stats.nb_visites, 0)
                    + (COALESCE(commande_stats.nb_commandes, 0) * 3)
                    + COALESCE(panier_stats.nb_ajouts_panier, 0)
                ) as score_popularite
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id = c.id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_visites
                FROM produits_visites
                GROUP BY produit_id
            ) visites_stats ON p.id = visites_stats.produit_id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_commandes
                FROM commande_produits
                GROUP BY produit_id
            ) commande_stats ON p.id = commande_stats.produit_id
            LEFT JOIN (
                SELECT produit_id, COUNT(*) as nb_ajouts_panier
                FROM panier
                GROUP BY produit_id
            ) panier_stats ON p.id = panier_stats.produit_id
            WHERE p.statut = 'actif'
              AND p.categorie_id = :categorie_id
            $exclude_sql
            ORDER BY score_popularite DESC, nb_visites DESC, p.date_creation DESC
            LIMIT :limit
        ");

        $stmt->bindValue(':categorie_id', $categorie_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            if ($key === 'categorie_id') {
                continue;
            }
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($produits) && function_exists('get_produits_by_categorie')) {
            $produits = get_produits_by_categorie($categorie_id);
            if (!empty($exclude_ids) && is_array($produits)) {
                $exclude_map = array_flip(array_map('intval', $exclude_ids));
                $produits = array_values(array_filter($produits, function ($produit) use ($exclude_map) {
                    return !isset($exclude_map[(int) ($produit['id'] ?? 0)]);
                }));
            }
            $produits = array_slice($produits, 0, $limit);
        }

        if (function_exists('model_produits_normalize')) {
            return model_produits_normalize($produits ? $produits : []);
        }

        return $produits ? $produits : [];
    } catch (PDOException $e) {
        if (function_exists('get_produits_by_categorie')) {
            $produits = get_produits_by_categorie($categorie_id);
            $produits = array_slice($produits ?: [], 0, $limit);
            if (function_exists('model_produits_normalize')) {
                return model_produits_normalize($produits);
            }
            return $produits;
        }
        return [];
    }
}

?>

