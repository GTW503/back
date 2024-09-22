<?php
// Autoriser les requêtes CORS
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Inclure la connexion à la base de données
require_once __DIR__ . '/../../config.db.php';

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Gérer l'envoi des feedbacks
    $data = file_get_contents("php://input");
    $decodedData = json_decode($data, true);

    if (isset($decodedData['message']) && !empty(trim($decodedData['message']))) {
        $message = trim($decodedData['message']);
        $sql = 'INSERT INTO feedback (message) VALUES (:message)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':message' => $message]);

        echo json_encode(['success' => true, 'message' => 'Feedback envoyé avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Le message ne peut pas être vide.']);
    }
} elseif ($method === 'PUT') {
    // Gérer la mise à jour du statut d'un feedback et de la notification
    $data = file_get_contents("php://input");
    $decodedData = json_decode($data, true);

    if (isset($decodedData['id']) && isset($decodedData['status']) && isset($decodedData['notification'])) {
        $feedbackId = $decodedData['id'];
        $status = $decodedData['status'];
        $notification = $decodedData['notification'];

        $sql = 'UPDATE feedback SET status = :status, notification = :notification WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':status' => $status, ':notification' => $notification, ':id' => $feedbackId]);

        echo json_encode(['success' => true, 'message' => 'Statut mis à jour et notification envoyée.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
    }
} elseif ($method === 'GET') {
    // Récupérer les feedbacks
    $sql = 'SELECT * FROM feedback ORDER BY date_sent DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'feedbacks' => $feedbacks]);
} else {
    echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
}
