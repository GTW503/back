<?php
require_once '../../config.db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Récupérer tous les fournisseurs sans la colonne categorie_nom
            $stmt = $pdo->prepare("
                SELECT f.id, f.fournisseur, f.adresse, f.telephone, f.email, f.categorie_produit, f.livraison
                FROM fournisseurs f
                ORDER BY f.id ASC
            ");
            $stmt->execute();
            $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Retourner les fournisseurs
            echo json_encode($fournisseurs);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Erreur lors de la récupération des fournisseurs',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        break;

    case 'POST':
        try {
            // Récupérer les données envoyées via JSON
            $data = json_decode(file_get_contents('php://input'), true);

            // Extraire les valeurs du JSON
            $fournisseur = $data['fournisseur'] ?? '';
            $adresse = $data['adresse'] ?? '';
            $telephone = $data['telephone'] ?? '';
            $email = $data['email'] ?? '';
            $categorieProduit = $data['categorie_produit'] ?? ''; // ID de la catégorie
            $livraison = $data['livraison'] ?? '';

            // Vérifier que tous les champs sont présents
            if ($fournisseur && $adresse && $telephone && $email && $categorieProduit && $livraison) {
                // Insérer les données dans la base de données
                $stmt = $pdo->prepare("INSERT INTO fournisseurs (fournisseur, adresse, telephone, email, categorie_produit, livraison) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$fournisseur, $adresse, $telephone, $email, $categorieProduit, $livraison])) {
                    echo json_encode(['message' => 'Fournisseur ajouté avec succès']);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erreur lors de l\'ajout du fournisseur']);
                }
            } else {
                http_response_code(400); // Mauvaise requête
                echo json_encode(['message' => 'Veuillez remplir tous les champs']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Erreur lors de l\'ajout du fournisseur', 'error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            // Récupérer les données envoyées via JSON
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';
            $fournisseur = $data['fournisseur'] ?? '';
            $adresse = $data['adresse'] ?? '';
            $telephone = $data['telephone'] ?? '';
            $email = $data['email'] ?? '';
            $categorieProduit = $data['categorie_produit'] ?? ''; // ID de la catégorie
            $livraison = $data['livraison'] ?? '';

            // Vérifier que tous les champs sont présents
            if ($id && $fournisseur && $adresse && $telephone && $email && $categorieProduit && $livraison) {
                // Mise à jour des informations du fournisseur
                $stmt = $pdo->prepare("UPDATE fournisseurs SET fournisseur = ?, adresse = ?, telephone = ?, email = ?, categorie_produit = ?, livraison = ? WHERE id = ?");
                if ($stmt->execute([$fournisseur, $adresse, $telephone, $email, $categorieProduit, $livraison, $id])) {
                    echo json_encode(['message' => 'Fournisseur modifié avec succès']);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erreur lors de la modification du fournisseur']);
                }
            } else {
                http_response_code(400); // Mauvaise requête
                echo json_encode(['message' => 'Veuillez remplir tous les champs']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Erreur lors de la modification du fournisseur', 'error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            // Récupérer les données envoyées via JSON
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';

            // Vérifier que l'ID est présent
            if ($id) {
                // Suppression du fournisseur
                $stmt = $pdo->prepare("DELETE FROM fournisseurs WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['message' => 'Fournisseur supprimé avec succès']);
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Erreur lors de la suppression du fournisseur']);
                }
            } else {
                http_response_code(400); // Mauvaise requête
                echo json_encode(['message' => 'ID du fournisseur manquant']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Erreur lors de la suppression du fournisseur', 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405); // Méthode non autorisée
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
}
