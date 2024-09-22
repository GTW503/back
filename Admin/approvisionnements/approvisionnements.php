<?php
require_once '../../config.db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
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
            // Récupérer les approvisionnements avec les noms des fournisseurs, produits, catégories et cuves sans afficher les ID
            $stmt = $pdo->prepare("
                SELECT a.id, a.date, a.stock_final, a.stock_arrive, a.melange, a.stock_total, a.montant_payer, 
                       f.fournisseur AS fournisseur_nom, p.designation AS produit_nom, 
                       c.nom AS categorie_nom, cu.nom AS cuve_nom, cu.capacite_stock
                FROM approvisionnements a
                JOIN fournisseurs f ON a.fournisseur = f.id
                JOIN produits p ON a.produit = p.id
                JOIN categories c ON a.categorie = c.id
                JOIN cuves cu ON a.emplacement = cu.id
            ");
            $stmt->execute();
            $approvisionnements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'approvisionnements' => $approvisionnements
            ]);
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la récupération des données', 'error' => $e->getMessage()]);
        }
        break;

    case 'POST':
        // Ajouter un nouvel approvisionnement
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $date = $data['date'] ?? '';
            $fournisseur = $data['fournisseur'] ?? '';
            $categorie = $data['categorie'] ?? '';
            $produit = $data['produit'] ?? '';
            $stockArrive = $data['stockArrive'] ?? 0;
            $melange = $data['melange'] ?? 0;
            $emplacement = $data['emplacement'] ?? '';
            $montantPayer = $data['montantPayer'] ?? 0;

            // Récupérer le dernier stock final et stock total du fournisseur, produit, catégorie et cuve
            $checkStmt = $pdo->prepare("
                SELECT stock_final, stock_total FROM approvisionnements 
                WHERE fournisseur = ? AND produit = ? AND categorie = ? AND emplacement = ?
                ORDER BY date DESC LIMIT 1
            ");
            $checkStmt->execute([$fournisseur, $produit, $categorie, $emplacement]);
            $lastStock = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $stockFinal = $lastStock['stock_total'] ?? 0; // Stock final = dernier stock total
            $stockTotal = $stockFinal + $stockArrive; // Nouveau stock total

            // Récupérer la capacité maximale de la cuve
            $cuveStmt = $pdo->prepare("SELECT capacite_stock FROM cuves WHERE id = ?");
            $cuveStmt->execute([$emplacement]);
            $capaciteStock = $cuveStmt->fetchColumn();

            // Vérifier que le stock total ne dépasse pas la capacité de la cuve
            if ($stockTotal > $capaciteStock) {
                echo json_encode(['message' => 'Le stock total dépasse la capacité maximale de la cuve.']);
                exit;
            }

            // Ajouter le nouvel approvisionnement
            $stmt = $pdo->prepare("
                INSERT INTO approvisionnements 
                (date, fournisseur, categorie, produit, stock_final, stock_arrive, melange, stock_total, emplacement, montant_payer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$date, $fournisseur, $categorie, $produit, $stockFinal, $stockArrive, $melange, $stockTotal, $emplacement, $montantPayer])) {
                echo json_encode(['message' => 'Approvisionnement créé avec succès']);
            } else {
                echo json_encode(['message' => 'Erreur lors de l\'insertion dans la base de données']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la création de l\'approvisionnement', 'error' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        // Mettre à jour un approvisionnement existant
        try {
            $data = json_decode(file_get_contents("php://input"), true);

            $id = $data['id'] ?? '';
            $date = $data['date'] ?? '';
            $fournisseur = $data['fournisseur'] ?? '';
            $categorie = $data['categorie'] ?? '';
            $produit = $data['produit'] ?? '';
            $stockArrive = $data['stock_arrive'] ?? 0;
            $melange = $data['melange'] ?? 0;
            $emplacement = $data['emplacement'] ?? '';
            $montantPayer = $data['montant_payer'] ?? 0;

            // Récupérer le dernier stock final et stock total du fournisseur, produit, catégorie et cuve
            $checkStmt = $pdo->prepare("
                SELECT stock_total FROM approvisionnements 
                WHERE fournisseur = ? AND produit = ? AND categorie = ? AND emplacement = ?
                ORDER BY date DESC LIMIT 1
            ");
            $checkStmt->execute([$fournisseur, $produit, $categorie, $emplacement]);
            $lastStock = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $stockFinal = $lastStock['stock_total'] ?? 0;
            $stockTotal = $stockFinal + $stockArrive;

            // Vérifier que le stock total ne dépasse pas la capacité de la cuve
            $cuveStmt = $pdo->prepare("SELECT capacite_stock FROM cuves WHERE id = ?");
            $cuveStmt->execute([$emplacement]);
            $capaciteStock = $cuveStmt->fetchColumn();

            if ($stockTotal > $capaciteStock) {
                echo json_encode(['message' => 'Le stock total dépasse la capacité maximale de la cuve.']);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE approvisionnements 
                SET date = ?, fournisseur = ?, categorie = ?, produit = ?, stock_final = ?, stock_arrive = ?, melange = ?, stock_total = ?, emplacement = ?, montant_payer = ?
                WHERE id = ?
            ");
            if ($stmt->execute([$date, $fournisseur, $categorie, $produit, $stockFinal, $stockArrive, $melange, $stockTotal, $emplacement, $montantPayer, $id])) {
                echo json_encode(['message' => 'Approvisionnement mis à jour avec succès']);
            } else {
                echo json_encode(['message' => 'Erreur lors de la mise à jour de l\'approvisionnement']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la mise à jour de l\'approvisionnement', 'error' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Supprimer un approvisionnement
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';

            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM approvisionnements WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['message' => 'Approvisionnement supprimé avec succès']);
                } else {
                    echo json_encode(['message' => 'Erreur lors de la suppression de l\'approvisionnement']);
                }
            } else {
                echo json_encode(['message' => 'ID de l\'approvisionnement manquant']);
            }
        } catch (Exception $e) {
            echo json_encode(['message' => 'Erreur lors de la suppression de l\'approvisionnement', 'error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
    }