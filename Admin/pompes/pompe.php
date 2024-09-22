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
            // Vérifier si des pompes existent
            $stmt = $pdo->query("SELECT COUNT(*) FROM pompes");
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                echo json_encode(['success' => false, 'message' => 'Aucune pompe dans la base de données']);
                exit;
            }

            // Jointure pour récupérer les pompes avec les désignations des produits et les noms des cuves
            $stmt = $pdo->prepare("
                SELECT 
                    pompes.id, 
                    pompes.nom, 
                    IFNULL(produits.designation, 'Produit non trouvé') AS produit_stocke, 
                    IFNULL(cuves.nom, 'Cuve non trouvée') AS cuve_associee
                FROM pompes
                LEFT JOIN produits ON pompes.contenu = produits.designation
                LEFT JOIN cuves ON pompes.cuve = cuves.id
            ");
            $stmt->execute();
            $pompes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($pompes) {
                echo json_encode(['success' => true, 'data' => $pompes]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Aucune pompe trouvée']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des pompes: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Ajouter une nouvelle pompe avec contrôle de doublon
        $data = json_decode(file_get_contents('php://input'), true);

        $nom = $data['nom'] ?? '';
        $contenu = $data['contenu'] ?? '';  // ID du produit (contenu)
        $cuve = $data['cuve'] ?? '';  // ID de la cuve

        // Vérifier que le nom, contenu et cuve sont valides
        if ($nom && $contenu && $cuve) {
            try {
                // Démarrer une transaction pour garantir l'intégrité des données
                $pdo->beginTransaction();

                // Vérifier s'il existe déjà une pompe avec le même nom, produit et cuve
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pompes WHERE nom = ? AND contenu = ? AND cuve = ?");
                $checkStmt->execute([$nom, $contenu, $cuve]);
                $exists = $checkStmt->fetchColumn();

                if ($exists > 0) {
                    echo json_encode(['success' => false, 'message' => 'Cette pompe existe déjà avec ce produit et dans cette cuve.']);
                    $pdo->rollBack();
                    exit;
                }

                // Insérer une nouvelle pompe
                $stmt = $pdo->prepare("INSERT INTO pompes (nom, contenu, cuve) VALUES (?, ?, ?)");
                if ($stmt->execute([$nom, $contenu, $cuve])) {
                    echo json_encode(['success' => true, 'message' => 'Pompe créée avec succès']);
                    $pdo->commit(); // Valider la transaction
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la pompe']);
                    $pdo->rollBack(); // Annuler la transaction en cas d'échec
                }
            } catch (Exception $e) {
                $pdo->rollBack(); // Annuler la transaction en cas d'erreur
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de la pompe: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
        }
        break;

    case 'PUT':
        // Modifier une pompe existante avec contrôle de doublon
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? '';
        $nom = $data['nom'] ?? '';
        $contenu = $data['contenu'] ?? '';  // ID du produit
        $cuve = $data['cuve'] ?? '';  // ID de la cuve

        // Vérifier que tous les champs sont valides
        if ($id && $nom && $contenu && $cuve) {
            try {
                // Démarrer une transaction
                $pdo->beginTransaction();

                // Vérifier s'il existe déjà une pompe avec le même nom, produit et cuve, exclure l'enregistrement en cours
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pompes WHERE nom = ? AND contenu = ? AND cuve = ? AND id != ?");
                $checkStmt->execute([$nom, $contenu, $cuve, $id]);
                $exists = $checkStmt->fetchColumn();

                if ($exists > 0) {
                    echo json_encode(['success' => false, 'message' => 'Une autre pompe avec le même nom, produit et cuve existe déjà.']);
                    $pdo->rollBack();
                    exit;
                }

                // Mettre à jour la pompe existante
                $stmt = $pdo->prepare("UPDATE pompes SET nom = ?, contenu = ?, cuve = ? WHERE id = ?");
                if ($stmt->execute([$nom, $contenu, $cuve, $id])) {
                    echo json_encode(['success' => true, 'message' => 'Pompe mise à jour avec succès']);
                    $pdo->commit(); // Valider la transaction
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la pompe']);
                    $pdo->rollBack(); // Annuler la transaction en cas d'échec
                }
            } catch (Exception $e) {
                $pdo->rollBack(); // Annuler la transaction en cas d'erreur
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification de la pompe: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
        }
        break;

    case 'DELETE':
        // Supprimer une pompe existante
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? '';

        if ($id) {
            try {
                // Démarrer une transaction
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("DELETE FROM pompes WHERE id = ?");
                if ($stmt->execute([$id])) {
                    echo json_encode(['success' => true, 'message' => 'Pompe supprimée avec succès']);
                    $pdo->commit(); // Valider la transaction
                } else {
                    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la pompe']);
                    $pdo->rollBack(); // Annuler la transaction en cas d'échec
                }
            } catch (Exception $e) {
                $pdo->rollBack(); // Annuler la transaction en cas d'erreur
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la pompe: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de la pompe manquant']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        break;
}
