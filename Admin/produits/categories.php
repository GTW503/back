<?php
require_once '../../config.db.php'; // Assurez-vous que ce chemin est correct et que config.db.php configure bien $pdo

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Récupérer les noms des catégories depuis la base de données
            $stmt = $pdo->prepare("SELECT nom FROM categories");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Retourner les catégories au format JSON
            echo json_encode(['categories' => $categories]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Erreur lors de la récupération des catégories',
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        try {
            // Ajouter une nouvelle catégorie
            $data = json_decode(file_get_contents('php://input'), true);
            $nom = trim($data['nom'] ?? '');

            if (!empty($nom)) {
                $stmt = $pdo->prepare("INSERT INTO categories (nom) VALUES (?)");
                if ($stmt->execute([$nom])) {
                    echo json_encode(['message' => 'Catégorie créée avec succès', 'id' => $pdo->lastInsertId()]);
                } else {
                    echo json_encode(['error' => 'Erreur lors de la création de la catégorie']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Le nom de la catégorie est requis']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Erreur lors de l\'ajout de la catégorie',
                'error' => $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405); // Méthode non autorisée
        echo json_encode(['message' => 'Méthode non autorisée']);
        break;
}
