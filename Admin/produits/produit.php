<?php
require_once '../../config.db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupérer tous les produits avec leurs catégories et détails supplémentaires
        $stmt = $pdo->prepare("SELECT produits.id, produits.designation, produits.prix_pompe, produits.unite_gros, produits.unite_detail, produits.capacite, categories.nom 
                               FROM produits 
                               JOIN categories ON produits.categorie_id = categories.id");
        $stmt->execute();
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($produits);
        break;

    case 'POST':
        // Ajouter un nouveau produit avec les informations supplémentaires
        $data = json_decode(file_get_contents('php://input'), true);
        $designation = trim($data['designation'] ?? '');
        $prixPompe = isset($data['prix_pompe']) ? (float)$data['prix_pompe'] : 0;
        $uniteGros = trim($data['unite_gros'] ?? '');
        $uniteDetail = trim($data['unite_detail'] ?? '');
        $capacite = trim($data['capacite'] ?? '');
        $categorieNom = trim($data['categorie_nom'] ?? ''); // Nom de la catégorie

        if ($designation && $prixPompe > 0 && $uniteGros && $uniteDetail && $capacite && $categorieNom) {
            // Vérifier si la catégorie existe
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
            $stmt->execute([$categorieNom]);
            $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categorie) {
                // La catégorie existe déjà
                $categorieId = $categorie['id'];
            } else {
                // Créer une nouvelle catégorie si elle n'existe pas
                $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                $stmt->execute([$categorieNom]);
                $categorieId = $pdo->lastInsertId();
            }

            // Ajouter le produit avec l'ID de la catégorie et les nouveaux champs
            $stmt = $pdo->prepare("INSERT INTO produits (designation, prix_pompe, unite_gros, unite_detail, capacite, categorie_id) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$designation, $prixPompe, $uniteGros, $uniteDetail, $capacite, $categorieId])) {
                echo json_encode(['message' => 'Produit et catégorie créés avec succès']);
            } else {
                echo json_encode(['error' => 'Erreur lors de la création du produit']);
            }
        } else {
            echo json_encode(['message' => 'Veuillez remplir tous les champs correctement']);
        }
        break;

    case 'PUT':
        // Modifier un produit existant avec les nouvelles informations
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $designation = trim($data['designation'] ?? '');
        $prixPompe = isset($data['prix_pompe']) ? (float)$data['prix_pompe'] : 0;
        $uniteGros = trim($data['unite_gros'] ?? '');
        $uniteDetail = trim($data['unite_detail'] ?? '');
        $capacite = trim($data['capacite'] ?? '');
        $categorieNom = trim($data['categorie_nom'] ?? ''); // Nom de la catégorie

        if ($id > 0 && $designation && $prixPompe > 0 && $uniteGros && $uniteDetail && $capacite && $categorieNom) {
            // Vérifier si la catégorie existe
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
            $stmt->execute([$categorieNom]);
            $categorie = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categorie) {
                $categorieId = $categorie['id'];
            } else {
                // Créer une nouvelle catégorie si elle n'existe pas
                $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                $stmt->execute([$categorieNom]);
                $categorieId = $pdo->lastInsertId();
            }

            // Mettre à jour le produit avec les nouvelles informations
            $stmt = $pdo->prepare("UPDATE produits SET designation = ?, prix_pompe = ?, unite_gros = ?, unite_detail = ?, capacite = ?, categorie_id = ? WHERE id = ?");
            if ($stmt->execute([$designation, $prixPompe, $uniteGros, $uniteDetail, $capacite, $categorieId, $id])) {
                echo json_encode(['message' => 'Produit mis à jour avec succès']);
            } else {
                echo json_encode(['error' => 'Erreur lors de la mise à jour du produit']);
            }
        } else {
            echo json_encode(['message' => 'Tous les champs sont obligatoires']);
        }
        break;

    case 'DELETE':
        // Supprimer un produit
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['message' => 'Produit supprimé avec succès']);
            } else {
                echo json_encode(['error' => 'Erreur lors de la suppression du produit']);
            }
        } else {
            echo json_encode(['message' => 'ID du produit manquant']);
        }
        break;

    default:
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
}
