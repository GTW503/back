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
        try {
            // Récupérer toutes les cuves avec les informations du produit stocké
            $stmt = $pdo->prepare("
                SELECT cuves.id, cuves.designation, cuves.nom, 
                       cuves.capacite_stock AS capacite_stock, 
                       cuves.prix_achat AS prix_achat, 
                       produits.designation AS produit_nom, 
                       cuves.created_at
                FROM cuves
                JOIN produits ON cuves.produit_stock = produits.id
                ORDER BY cuves.id ASC
            ");
            $stmt->execute();
            $cuves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($cuves) {
                echo json_encode(['success' => true, 'data' => $cuves]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Aucune cuve trouvée']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des cuves : ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Ajouter une nouvelle cuve
        $data = json_decode(file_get_contents('php://input'), true);
        $designation = trim($data['designation'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $produitStock = isset($data['produit_stock']) ? (int)$data['produit_stock'] : 0;
        $capaciteStock = isset($data['capacite_stock']) ? (int)$data['capacite_stock'] : 0;
        $prixAchat = isset($data['prix_achat']) ? (float)$data['prix_achat'] : 0;

        if ($designation && $nom && $produitStock > 0 && $capaciteStock > 0 && $prixAchat > 0) {
            try {
                // Vérifier si une cuve avec la même désignation ou nom existe déjà
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM cuves WHERE designation = ? OR nom = ?");
                $checkStmt->execute([$designation, $nom]);
                $exists = $checkStmt->fetchColumn();

                if ($exists > 0) {
                    // La cuve existe déjà
                    echo json_encode(['success' => false, 'message' => 'Une cuve avec cette désignation ou ce nom existe déjà']);
                } else {
                    // Insérer la nouvelle cuve
                    $stmt = $pdo->prepare("INSERT INTO cuves (designation, nom, produit_stock, capacite_stock, prix_achat) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$designation, $nom, $produitStock, $capaciteStock, $prixAchat])) {
                        echo json_encode(['success' => true, 'message' => 'Cuve créée avec succès']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la cuve']);
                    }
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la création : ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs correctement']);
        }
        break;

    case 'PUT':
        // Modifier une cuve existante
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $designation = trim($data['designation'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $produitStock = isset($data['produit_stock']) ? (int)$data['produit_stock'] : 0;
        $capaciteStock = isset($data['capacite_stock']) ? (int)$data['capacite_stock'] : 0;
        $prixAchat = isset($data['prix_achat']) ? (float)$data['prix_achat'] : 0;

        if ($id > 0 && $designation && $nom && $produitStock > 0 && $capaciteStock > 0 && $prixAchat > 0) {
            try {
                // Vérifier si une autre cuve avec la même désignation ou nom existe
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM cuves WHERE (designation = ? OR nom = ?) AND id != ?");
                $checkStmt->execute([$designation, $nom, $id]);
                $exists = $checkStmt->fetchColumn();

                if ($exists > 0) {
                    // Une autre cuve avec la même désignation ou nom existe déjà
                    echo json_encode(['success' => false, 'message' => 'Une autre cuve avec cette désignation ou ce nom existe déjà']);
                } else {
                    // Mettre à jour la cuve
                    $stmt = $pdo->prepare("UPDATE cuves SET designation = ?, nom = ?, produit_stock = ?, capacite_stock = ?, prix_achat = ? WHERE id = ?");
                    if ($stmt->execute([$designation, $nom, $produitStock, $capaciteStock, $prixAchat, $id])) {
                        echo json_encode(['success' => true, 'message' => 'Cuve mise à jour avec succès']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la cuve']);
                    }
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs correctement']);
        }
        break;

    case 'DELETE':
        // Supprimer une cuve
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if ($id > 0) {
            try {
                // Supprimer la cuve
                $stmt = $pdo->prepare("DELETE FROM cuves WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true, 'message' => 'Cuve supprimée avec succès']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la cuve']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de la cuve manquant']);
        }
        break;

    case 'OPTIONS':
        header("HTTP/1.1 204 No Content");
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        break;
}
