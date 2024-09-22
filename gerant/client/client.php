<?php
// Autoriser l'origine spécifique de votre frontend (par exemple, http://localhost:5173)
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS (pré-vol) pour les requêtes CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Inclure le fichier de connexion à la base de données
require_once '../../config.db.php'; // Chemin vers le fichier de configuration

// Gérer les requêtes en fonction de la méthode HTTP
switch ($method) {
    case 'POST':
        // Ajouter un client
        $data = json_decode(file_get_contents("php://input"), true);
        $nom = $data['nom'];
        $prenom = $data['prenom'];
        $telephone = $data['telephone'];
        $email = $data['email'];

        // Vérifier si tous les champs sont remplis
        if (empty($nom) || empty($prenom) || empty($telephone) || empty($email)) {
            echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires."]);
            exit();
        }

        // Vérifier si un client avec le même numéro de téléphone existe déjà
        $checkSql = "SELECT * FROM clients WHERE telephone = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$telephone]);
        $existingClient = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingClient) {
            // Si un client avec ce numéro de téléphone existe déjà
            echo json_encode(["success" => false, "message" => "Un client avec ce numéro de téléphone existe déjà."]);
            exit();
        }

        // Si pas de doublon, on procède à l'insertion du nouveau client
        $sql = "INSERT INTO clients (nom, prenom, telephone, email) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$nom, $prenom, $telephone, $email])) {
            echo json_encode(["success" => true, "message" => "Client ajouté avec succès."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erreur lors de l'ajout du client."]);
        }
        break;

    case 'GET':
        // Lire tous les clients
        $sql = "SELECT * FROM clients";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($clients);
        break;

    case 'PUT':
        // Mettre à jour un client
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $nom = $data['nom'];
        $prenom = $data['prenom'];
        $telephone = $data['telephone'];
        $email = $data['email'];

        if (empty($id) || empty($nom) || empty($prenom) || empty($telephone) || empty($email)) {
            echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires."]);
            exit();
        }

        // Mise à jour du client dans la base de données
        $sql = "UPDATE clients SET nom = ?, prenom = ?, telephone = ?, email = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$nom, $prenom, $telephone, $email, $id])) {
            echo json_encode(["success" => true, "message" => "Client mis à jour avec succès."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour du client."]);
        }
        break;

    case 'DELETE':
        // Supprimer un client
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];

        if (empty($id)) {
            echo json_encode(["success" => false, "message" => "L'ID du client est requis."]);
            exit();
        }

        // Suppression du client dans la base de données
        $sql = "DELETE FROM clients WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$id])) {
            echo json_encode(["success" => true, "message" => "Client supprimé avec succès."]);
        } else {
            echo json_encode(["success" => false, "message" => "Erreur lors de la suppression du client."]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
        break;
}

// Fermer la connexion à la base de données
$pdo = null;
