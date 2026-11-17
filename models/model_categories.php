<?php
/**
 * Modèle pour la gestion des catégories
 * Programmation procédurale uniquement
 */

// Inclusion du fichier de connexion à la BDD
require_once __DIR__ . '/../conn/conn.php';
require_once __DIR__ . '/../includes/apply_fk_suppression_produit.php';
require_once __DIR__ . '/../includes/text_encoding.php';

/**
 * Récupère toutes les catégories actives
 * @return array|false Tableau des catégories ou False en cas d'erreur
 */
function get_all_categories()
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories ORDER BY nom ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return normalize_utf8_rows($categories ? $categories : []);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère une catégorie par son ID
 * @param int $id L'ID de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_id($id)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? normalize_utf8_rows($categorie) : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Suggestions catégories pour l'autocomplétion de la barre de recherche
 * @param string $recherche Terme saisi
 * @param int $limit Nombre max de résultats
 * @return array
 */
function search_categories_suggestions($recherche, $limit = 4)
{
    global $db;

    $recherche = trim((string) $recherche);
    if ($recherche === '' || mb_strlen($recherche) < 2) {
        return [];
    }

    $limit = max(1, min(8, (int) $limit));

    try {
        $term = '%' . $recherche . '%';
        $stmt = $db->prepare("
            SELECT id, nom, image
            FROM categories
            WHERE nom LIKE :term
            ORDER BY
                CASE WHEN nom LIKE :starts THEN 0 ELSE 1 END,
                nom ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':term', $term, PDO::PARAM_STR);
        $stmt->bindValue(':starts', $recherche . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return normalize_utf8_rows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère une catégorie par son nom
 * @param string $nom Le nom de la catégorie
 * @return array|false Les données de la catégorie ou False si non trouvée
 */
function get_categorie_by_nom($nom)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE nom = :nom");
        $stmt->execute(['nom' => $nom]);
        $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

        return $categorie ? normalize_utf8_rows($categorie) : false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Crée une nouvelle catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @return int|false L'ID de la catégorie créée ou False en cas d'erreur
 */
function create_categorie($nom, $description = null, $image = null)
{
    global $db;

    try {
        $stmt = $db->prepare("
            INSERT INTO categories (nom, description, image, date_creation) 
            VALUES (:nom, :description, :image, NOW())
        ");

        $result = $stmt->execute([
            'nom' => $nom,
            'description' => $description,
            'image' => $image
        ]);

        if ($result) {
            return $db->lastInsertId();
        }

        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Met à jour une catégorie
 * @param int $id L'ID de la catégorie
 * @param string $nom Le nom de la catégorie
 * @param string $description La description
 * @param string|null $image Le chemin de l'image
 * @return bool True en cas de succès, False sinon
 */
function update_categorie($id, $nom, $description = null, $image = null)
{
    global $db;

    try {
        $stmt = $db->prepare("
            UPDATE categories SET
                nom = :nom,
                description = :description,
                image = :image
            WHERE id = :id
        ");

        return $stmt->execute([
            'id' => $id,
            'nom' => $nom,
            'description' => $description,
            'image' => $image
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Supprime une catégorie
 * @param int $id L'ID de la catégorie
 * @return bool True en cas de succès, False sinon
 */
function delete_categorie($id)
{
    global $db;

    try {
        // ALTER éventuel : hors logique transactionnelle simple
        ensure_fk_suppression_produit($db);

        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si une catégorie a des produits associés
 * @param int $categorie_id L'ID de la catégorie
 * @return bool True si la catégorie a des produits, False sinon
 */
function categorie_has_produits($categorie_id)
{
    global $db;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id = :id");
        $stmt->execute(['id' => $categorie_id]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère toutes les catégories avec le nombre de produits
 * @return array Tableau des catégories avec le nombre de produits
 */
function get_all_categories_with_count()
{
    global $db;

    try {
        $stmt = $db->prepare("
            SELECT c.*, COUNT(p.id) as nb_produits,
            (
                SELECT p2.image_principale
                FROM produits p2
                WHERE p2.categorie_id = c.id
                  AND p2.statut = 'actif'
                  AND p2.image_principale IS NOT NULL
                  AND TRIM(p2.image_principale) != ''
                ORDER BY p2.date_creation DESC, p2.id DESC
                LIMIT 1
            ) as image_produit
            FROM categories c
            LEFT JOIN produits p ON c.id = p.categorie_id AND p.statut = 'actif'
            GROUP BY c.id
            ORDER BY c.nom ASC
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return normalize_utf8_rows($categories ? $categories : []);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Récupère les catégories les plus populaires (visites, commandes, nombre de produits)
 * @param int $limit Nombre maximum de catégories à retourner
 * @return array Tableau des catégories triées par popularité décroissante
 */
function get_top_categories($limit = 4)
{
    global $db;

    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                COALESCE(visites_stats.nb_visites, 0) as nb_visites,
                COALESCE(commandes_stats.nb_commandes, 0) as nb_commandes,
                COALESCE(produits_stats.nb_produits, 0) as nb_produits,
                (
                    COALESCE(visites_stats.nb_visites, 0)
                    + (COALESCE(commandes_stats.nb_commandes, 0) * 3)
                ) as score_popularite
            FROM categories c
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(pv.id) as nb_visites
                FROM produits_visites pv
                INNER JOIN produits p ON pv.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) visites_stats ON c.id = visites_stats.categorie_id
            LEFT JOIN (
                SELECT p.categorie_id, COUNT(cp.id) as nb_commandes
                FROM commande_produits cp
                INNER JOIN produits p ON cp.produit_id = p.id
                WHERE p.statut = 'actif'
                GROUP BY p.categorie_id
            ) commandes_stats ON c.id = commandes_stats.categorie_id
            LEFT JOIN (
                SELECT categorie_id, COUNT(*) as nb_produits
                FROM produits
                WHERE statut = 'actif'
                GROUP BY categorie_id
            ) produits_stats ON c.id = produits_stats.categorie_id
            ORDER BY score_popularite DESC, nb_produits DESC, c.nom ASC
            LIMIT :limit
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($categories)) {
            $categories = get_all_categories();
            if (!empty($categories)) {
                $categories = array_slice($categories, 0, $limit);
            }
        }

        return $categories ? $categories : [];
    } catch (PDOException $e) {
        $categories = get_all_categories();
        if (!empty($categories)) {
            $categories = array_slice($categories, 0, $limit);
        }
        return $categories ? $categories : [];
    }
}

?>